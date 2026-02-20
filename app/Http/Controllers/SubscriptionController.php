<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Http\Requests\UpgradePlanRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\TaxCalculator;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected PaymentManager $paymentManager,
        protected TaxCalculator $taxCalculator,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentSubscription = $user?->effectiveSubscription();
        $currentPlanId = $currentSubscription?->plan_id;

        $plans = SubscriptionPlan::query()
            ->with([
                'campaigns:id,reward_name,category_id,status',
                'campaigns.category:id,name',
                'couponCategories:id,name,icon',
            ])
            ->get()
            ->map(fn (SubscriptionPlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => (float) $plan->price,
                'is_default' => $plan->is_default,
                'stamps_on_purchase' => $plan->stamps_on_purchase,
                'stamps_per_100' => $plan->stamps_per_100,
                'max_discounted_bills' => $plan->max_discounted_bills,
                'max_redeemable_amount' => $plan->max_redeemable_amount,
                'duration_days' => $plan->duration_days,
                'campaigns' => $plan->campaigns->map(fn ($campaign) => [
                    'id' => $campaign->id,
                    'reward_name' => $campaign->reward_name,
                    'category_name' => $campaign->category?->name,
                    'status' => $campaign->status,
                ]),
                'coupon_categories' => $plan->couponCategories->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'icon' => $cat->icon,
                ]),
                'campaign_count' => $plan->campaigns->count(),
                'coupon_category_count' => $plan->couponCategories->count(),
            ]);

        // Get the campaigns available under the user's current plan for primary campaign selection
        $availableCampaigns = [];
        if ($currentPlanId) {
            $currentPlan = SubscriptionPlan::with('campaigns:id,reward_name')->find($currentPlanId);
            $availableCampaigns = $currentPlan?->campaigns->map(fn ($c) => [
                'id' => $c->id,
                'reward_name' => $c->reward_name,
            ])->toArray() ?? [];
        }

        return Inertia::render('Subscriptions/Index', [
            'plans' => $plans,
            'currentSubscription' => $currentSubscription ? [
                'plan_id' => $currentSubscription->plan_id,
                'status' => $currentSubscription->status,
                'expires_at' => $currentSubscription->expires_at?->toDateString(),
            ] : null,
            'primaryCampaignId' => $user?->primary_campaign_id,
            'availableCampaigns' => $availableCampaigns,
            'isLoggedIn' => (bool) $user,
            'appDebug' => ! app()->isProduction(),
        ]);
    }

    public function upgrade(UpgradePlanRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // No downgrade allowed — only upgrade to non-default plans
        if ($plan->is_default) {
            return back()->with('error', 'You cannot manually switch to the base plan.');
        }

        $planPrice = (float) $plan->price;

        // Free plan — direct activation without payment
        if ($planPrice <= 0) {
            $this->subscriptionService->upgradePlan($user, $plan->id);
            $user->refresh();

            if (! $user->primary_campaign_id) {
                return back()->with([
                    'success' => 'Upgraded to '.$plan->name.' successfully! Please select your primary campaign.',
                    'needsCampaignSelection' => true,
                ]);
            }

            return back()->with('success', 'Upgraded to '.$plan->name.' successfully!');
        }

        // Paid plan — calculate tax and create Razorpay order
        $priceInPaise = (int) round($planPrice * 100);
        $taxType = config('app.plan_tax_type', 'exclusive');
        $taxBreakdown = $this->taxCalculator->calculatePlanTotal($priceInPaise, $taxType);

        $idempotencyKey = 'plan_'.$user->id.'_'.$plan->id.'_'.Str::uuid();

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'original_bill_amount' => $taxBreakdown['base'] / 100,
            'amount' => $taxBreakdown['base'] / 100,
            'platform_fee' => 0,
            'gst_amount' => $taxBreakdown['gst'] / 100,
            'total_amount' => $taxBreakdown['total'] / 100,
            'payment_gateway' => $this->paymentManager->getDefaultDriver(),
            'payment_status' => PaymentStatus::Pending,
            'type' => TransactionType::PlanPurchase,
            'idempotency_key' => $idempotencyKey,
            'commission_amount' => 0,
        ]);

        // Non-production mode: skip payment gateway and auto-complete
        if (! app()->isProduction()) {
            $transaction->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_id' => 'debug_'.uniqid(),
            ]);

            $this->subscriptionService->upgradePlan($user, $plan->id, $transaction);
            $user->refresh();

            if (! $user->primary_campaign_id) {
                return back()->with([
                    'success' => 'Debug mode: Upgraded to '.$plan->name.' successfully! Please select your primary campaign.',
                    'needsCampaignSelection' => true,
                ]);
            }

            return back()->with('success', 'Debug mode: Upgraded to '.$plan->name.' successfully!');
        }

        // Create Razorpay order
        try {
            $gateway = $this->paymentManager->driver();
            $order = $gateway->createPlanOrder($transaction);
            $transaction->update(['razorpay_order_id' => $order['id']]);

            return response()->json([
                'order' => $order,
                'transaction_id' => $transaction->id,
                'plan_id' => $plan->id,
            ]);
        } catch (\Exception $e) {
            $transaction->update(['payment_status' => PaymentStatus::Failed]);

            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Verify payment for a plan purchase and activate subscription.
     */
    public function verifyPlanPayment(VerifyPaymentRequest $request, Transaction $transaction): RedirectResponse
    {
        $gateway = $this->paymentManager->driver($transaction->payment_gateway);

        if ($gateway->verifyPayment($request->all())) {
            $transaction->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_id' => $request->input('razorpay_payment_id'),
            ]);

            // Find the plan from the transaction's idempotency key or notes
            $planId = $request->input('plan_id');
            $plan = SubscriptionPlan::findOrFail($planId);

            $user = $transaction->user;
            $this->subscriptionService->upgradePlan($user, $plan->id, $transaction);
            $user->refresh();

            if (! $user->primary_campaign_id) {
                return redirect()->route('subscriptions.index')->with([
                    'success' => 'Payment successful! Upgraded to '.$plan->name.'. Please select your primary campaign.',
                    'needsCampaignSelection' => true,
                ]);
            }

            return redirect()->route('subscriptions.index')
                ->with('success', 'Payment successful! Upgraded to '.$plan->name.'.');
        }

        return redirect()->route('subscriptions.index')
            ->with('error', 'Payment verification failed. Please contact support.');
    }

    /**
     * Set the user's primary campaign from their plan's available campaigns.
     */
    public function setPrimaryCampaign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        $user = $request->user();
        $success = $this->subscriptionService->setPrimaryCampaign($user, $validated['campaign_id']);

        if (! $success) {
            return back()->with('error', 'This campaign is not available under your current plan.');
        }

        return back()->with('success', 'Primary campaign set successfully!');
    }
}

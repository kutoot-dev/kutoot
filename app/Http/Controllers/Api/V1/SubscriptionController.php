<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpgradePlanRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Http\Resources\StampResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserSubscriptionResource;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\TaxCalculator;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

/**
 * @tags Subscriptions
 */
class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected PaymentManager $paymentManager,
        protected TaxCalculator $taxCalculator,
    ) {}

    /**
     * List subscription plans
     *
     * Returns all available subscription plans with their associated campaigns and coupon categories.
     */
    public function plans(): AnonymousResourceCollection
    {
        $plans = SubscriptionPlan::query()
            ->with([
                'campaigns' => fn ($q) => $q->where('is_active', true)->where('status', 'active')->withCount('stamps'),
                'couponCategories:id,name,icon',
            ])
            ->ordered()
            ->get();

        return SubscriptionPlanResource::collection($plans);
    }

    /**
     * Current subscription
     *
     * Returns the user's current active subscription with plan details.
     *
     * @response 200 { "data": { "id": 1, "status": "active", "plan": {} } }
     * @response 200 { "data": null, "message": "No active subscription" }
     */
    public function current(Request $request): JsonResponse
    {
        $subscription = $request->user()->effectiveSubscription();

        if (! $subscription || ! $subscription->exists) {
            $basePlan = SubscriptionPlan::where('is_default', true)->first();

            return response()->json([
                'data' => null,
                'base_plan' => $basePlan ? new SubscriptionPlanResource($basePlan) : null,
                'message' => 'No active paid subscription. Using base plan.',
            ]);
        }

        $subscription->load('plan');

        return response()->json([
            'data' => new UserSubscriptionResource($subscription),
        ]);
    }

    /**
     * Upgrade plan
     *
     * Initiates a plan upgrade. Free plans are activated immediately. Paid plans
     * create a Razorpay order that must be completed by the client.
     *
     * @response 200 { "message": "Upgraded successfully!", "requires_payment": false }
     * @response 200 { "order": {}, "transaction_id": 1, "plan_id": 2, "requires_payment": true }
     */
    public function upgrade(UpgradePlanRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        if ($plan->is_default) {
            return response()->json(['error' => 'You cannot manually switch to the base plan.'], 422);
        }

        // Check if user is already on this plan
        $currentSub = $user->effectiveSubscription();
        if ($currentSub && $currentSub->plan_id === $plan->id && $currentSub->exists) {
            return response()->json(['error' => 'You are already subscribed to this plan.'], 422);
        }

        $planPrice = (float) $plan->price;
        $campaignSelections = $validated['campaign_selections'] ?? [];

        // Free plan — direct activation
        if ($planPrice <= 0) {
            $this->subscriptionService->upgradePlan($user, $plan->id, campaignSelections: $campaignSelections);

            // Gather stamps awarded for this plan purchase
            $stamps = $user->stamps()
                ->where('source', 'plan_purchase')
                ->whereHas('transaction', fn ($q) => $q->where('payment_id', 'like', 'PLAN-'.$plan->id.'-%'))
                ->with('campaign')
                ->latest()
                ->take($plan->stamps_on_purchase)
                ->get();

            return response()->json([
                'message' => "Upgraded to {$plan->name} successfully!",
                'requires_payment' => false,
                'needs_campaign_selection' => ! $user->fresh()->primary_campaign_id,
                'stamps_awarded' => $stamps->count(),
                'stamps' => StampResource::collection($stamps),
            ]);
        }

        // Paid plan — create Razorpay order
        $priceInPaise = (int) round($planPrice * 100);
        $taxType = config('app.plan_tax_type', 'exclusive');
        $taxBreakdown = $this->taxCalculator->calculatePlanTotal($priceInPaise, $taxType);

        $idempotencyKey = 'plan_'.$user->id.'_'.$plan->id.'_'.Str::uuid();

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

        try {
            $gateway = $this->paymentManager->driver();
            $order = $gateway->createPlanOrder($transaction);
            $transaction->update(['razorpay_order_id' => $order['id']]);

            return response()->json([
                'order' => $order,
                'transaction_id' => $transaction->id,
                'plan_id' => $plan->id,
                'requires_payment' => true,
            ]);
        } catch (\Exception $e) {
            $transaction->update(['payment_status' => PaymentStatus::Failed]);

            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Verify plan payment
     *
     * Verifies the Razorpay payment for a plan upgrade and activates the subscription.
     *
     * @response 200 { "message": "Payment verified! Upgraded to Premium.", "transaction": {} }
     * @response 422 { "error": "Payment verification failed." }
     */
    public function verifyPayment(VerifyPaymentRequest $request): JsonResponse
    {
        $transaction = Transaction::where('razorpay_order_id', $request->input('razorpay_order_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Idempotency: if already completed or paid, return success
        if ($transaction->payment_status === PaymentStatus::Completed) {
            $stamps = $transaction->fresh()->stamps()->with('campaign')->get();
            return response()->json([
                'message' => "Payment already verified! Plan activation complete.",
                'transaction' => new TransactionResource($transaction->fresh()->load('stamps')),
                'needs_campaign_selection' => ! $request->user()->primary_campaign_id,
                'stamps_awarded' => $stamps->count(),
                'stamps' => $stamps->map(fn ($stamp) => [
                    'campaign_id' => $stamp->campaign_id,
                    'campaign_name' => $stamp->campaign?->name,
                    'count' => 1,
                ])->groupBy('campaign_id')->map(fn ($group) => [
                    'campaign_id' => $group[0]['campaign_id'],
                    'campaign_name' => $group[0]['campaign_name'],
                    'count' => $group->count(),
                ])->values()->toArray(),
            ]);
        }

        $gateway = $this->paymentManager->driver($transaction->payment_gateway);

        if (! $gateway->verifyPayment($request->all())) {
            Log::warning('Payment signature verification failed', [
                'user_id' => $request->user()->id,
                'order_id' => $request->input('razorpay_order_id'),
            ]);
            return response()->json(['error' => 'Payment verification failed. Please try again.'], 422);
        }

        $transaction->update([
            'payment_status' => PaymentStatus::Paid,
            'payment_id' => $request->input('razorpay_payment_id'),
        ]);

        $planId = $request->input('plan_id');
        $plan = SubscriptionPlan::findOrFail($planId);
        $user = $transaction->user;

        // Retrieve campaign selections from the request
        $campaignSelections = $request->input('campaign_selections', []);

        try {
            $this->subscriptionService->upgradePlan($user, $plan->id, $transaction, $campaignSelections);
        } catch (\Exception $e) {
            // Payment is already verified and marked as Paid.
            // Log the error but don't fail the entire request.
            Log::error('Plan upgrade after payment failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'message' => "Payment verified! However, plan activation encountered an issue: {$e->getMessage()}. Please contact support.",
                'transaction' => new TransactionResource($transaction->fresh()->load('stamps')),
                'needs_campaign_selection' => true,
                'stamps_awarded' => 0,
                'stamps' => [],
                'partial_error' => $e->getMessage(),
            ]);
        }

        // Gather stamps awarded for this transaction
        $stamps = $transaction->fresh()->stamps()->with('campaign')->get();

        return response()->json([
            'message' => "Payment verified! Upgraded to {$plan->name}.",
            'transaction' => new TransactionResource($transaction->fresh()->load('stamps')),
            'needs_campaign_selection' => ! $user->fresh()->primary_campaign_id,
            'stamps_awarded' => $stamps->count(),
            'stamps' => $stamps->map(fn ($stamp) => [
                'campaign_id' => $stamp->campaign_id,
                'campaign_name' => $stamp->campaign?->name,
                'count' => 1,
            ])->groupBy('campaign_id')->map(fn ($group) => [
                'campaign_id' => $group[0]['campaign_id'],
                'campaign_name' => $group[0]['campaign_name'],
                'count' => $group->count(),
            ])->values()->toArray(),
        ]);
    }

    /**
     * Set primary campaign
     *
     * Sets the user's primary campaign from their plan's available campaigns.
     *
     * @response 200 { "message": "Primary campaign set successfully!" }
     * @response 422 { "error": "This campaign is not available under your current plan." }
     */
    public function setPrimaryCampaign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => ['required', 'exists:campaigns,id'],
        ]);

        $user = $request->user();
        $success = $this->subscriptionService->setPrimaryCampaign($user, $validated['campaign_id']);

        if (! $success) {
            return response()->json([
                'error' => 'This campaign is not available under your current plan.',
            ], 422);
        }

        return response()->json([
            'message' => 'Primary campaign set successfully!',
        ]);
    }

    /**
     * Available campaigns
     *
     * Returns the user's subscribed active campaigns with primary flag.
     * Used for campaign selection before stamp generation.
     */
    public function availableCampaigns(Request $request): JsonResponse
    {
        $user = $request->user();
        $primaryCampaignId = $user->primary_campaign_id;

        $campaigns = $user->campaigns()
            ->where('is_active', true)
            ->where('status', 'active')
            ->withPivot('is_primary', 'subscribed_at')
            ->get()
            ->map(fn ($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->reward_name ?? $campaign->code ?? "Campaign #{$campaign->id}",
                'code' => $campaign->code,
                'description' => $campaign->description,
                'stamp_target' => $campaign->stamp_target,
                'issued_stamps' => $campaign->issued_stamps_cache,
                'is_primary' => $campaign->id === $primaryCampaignId,
                'image' => $campaign->getFirstMediaUrl('media', 'thumb'),
            ]);

        return response()->json([
            'data' => $campaigns,
            'primary_campaign_id' => $primaryCampaignId,
        ]);
    }
}

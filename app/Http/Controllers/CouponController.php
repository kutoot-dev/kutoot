<?php

namespace App\Http\Controllers;

use App\Events\CommissionEarned;
use App\Models\DiscountCoupon;
use App\Models\MerchantLocation;
use App\Models\Transaction;
use App\Services\CouponRedemptionService;
use App\Services\Payments\PaymentManager;
use App\Services\StampService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CouponController extends Controller
{
    public function __construct(
        protected CouponRedemptionService $redemptionService,
        protected PaymentManager $paymentManager,
        protected StampService $stampService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $planId = $user?->activeSubscription?->plan_id;
        $plan = $user?->activeSubscription?->plan;

        $coupons = DiscountCoupon::query()
            ->when($planId, fn ($q) => $q->forPlan($planId))
            ->active()
            ->with(['merchantLocation.merchant', 'category'])
            ->latest()
            ->paginate(9);

        $locations = MerchantLocation::with('merchant')->get()->map(fn ($loc) => [
            'id' => $loc->id,
            'name' => $loc->branch_name.' ('.$loc->merchant->name.')',
        ]);

        // Campaigns the user can choose from (based on their plan)
        $availableCampaigns = $plan
            ? $plan->campaigns()->where('status', 'active')->get(['campaigns.id', 'reward_name'])
            : collect();

        // Calculate remaining redeemable amount for the user's plan
        $totalDiscountRedeemed = $user ? (float) $user->couponRedemptions()->sum('discount_applied') : 0;
        $maxRedeemableAmount = $plan ? (float) $plan->max_redeemable_amount : 0;
        $remainingRedeemAmount = max(0, $maxRedeemableAmount - $totalDiscountRedeemed);

        return Inertia::render('Coupons/Index', [
            'coupons' => $coupons,
            'locations' => $locations,
            'planName' => $plan->name ?? 'Free Tier',
            'stampsPerHundred' => $plan->stamps_per_100 ?? 0,
            'primaryCampaign' => $user?->primaryCampaign ? [
                'id' => $user->primaryCampaign->id,
                'reward_name' => $user->primaryCampaign->reward_name,
            ] : null,
            'availableCampaigns' => $availableCampaigns,
            'isLoggedIn' => (bool) $user,
            'appDebug' => config('app.debug'),
            'maxRedeemableAmount' => $maxRedeemableAmount,
            'remainingRedeemAmount' => $remainingRedeemAmount,
        ]);
    }

    public function redeem(Request $request, DiscountCoupon $coupon)
    {
        $validated = $request->validate([
            'merchant_location_id' => 'required|exists:merchant_locations,id',
            'amount' => 'required|numeric|min:0.01',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'You must be logged in to redeem a coupon.');
        }

        // If user has no primary campaign and selected one, set it now
        if (! $user->primary_campaign_id && ! empty($validated['campaign_id'])) {
            $user->update(['primary_campaign_id' => $validated['campaign_id']]);
            $user->refresh();
        }

        $merchantLocation = MerchantLocation::with('merchant')->findOrFail($validated['merchant_location_id']);
        $amount = (float) $validated['amount'];

        try {
            return DB::transaction(function () use ($user, $coupon, $merchantLocation, $amount) {
                // 1. Calculate platform fees & GST
                $platformFee = config('app.platform_fee', 10);
                if (config('app.platform_fee_type') === 'percentage') {
                    $platformFee = ($amount * $platformFee) / 100;
                }
                $gstAmount = ($platformFee * config('app.gst_rate', 18)) / 100;

                // 2. Calculate discount
                $discountAmount = 0.0;
                if ($coupon->discount_type === \App\Enums\DiscountType::Fixed) {
                    $discountAmount = (float) $coupon->discount_value;
                } else {
                    $discountAmount = ($amount * (float) $coupon->discount_value) / 100;
                }

                if ($coupon->max_discount_amount) {
                    $discountAmount = min($discountAmount, (float) $coupon->max_discount_amount);
                }

                $finalBillAfterDiscount = max(0, $amount - $discountAmount);
                $grandTotal = $finalBillAfterDiscount + $platformFee + $gstAmount;

                // 3. Calculate commission for the merchant location
                $commissionAmount = ($finalBillAfterDiscount * $merchantLocation->commission_percentage) / 100;

                // 4. Create Transaction
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'coupon_id' => $coupon->id,
                    'merchant_location_id' => $merchantLocation->id,
                    'original_bill_amount' => $amount,
                    'discount_amount' => $discountAmount,
                    'amount' => $finalBillAfterDiscount,
                    'platform_fee' => $platformFee,
                    'gst_amount' => $gstAmount,
                    'total_amount' => $grandTotal,
                    'payment_gateway' => $this->paymentManager->getDefaultDriver(),
                    'payment_status' => 'pending',
                    'commission_amount' => $commissionAmount,
                ]);

                $financials = [
                    'original_bill_amount' => $amount,
                    'discount_amount' => $discountAmount,
                    'platform_fee' => $platformFee,
                    'gst_amount' => $gstAmount,
                    'total_paid' => $grandTotal,
                ];

                // 5. Debug mode: skip payment gateway and auto-complete
                if (config('app.debug')) {
                    $transaction->update(['payment_status' => 'paid', 'payment_id' => 'debug_'.uniqid()]);

                    // Complete coupon redemption
                    if ($transaction->coupon_id) {
                        $coupon_model = DiscountCoupon::find($transaction->coupon_id);
                        if ($coupon_model) {
                            $this->redemptionService->redeemCoupon($user, $coupon_model, $transaction, $financials);
                        }
                    }

                    // Award stamps
                    $this->stampService->awardStampsForBill($transaction);

                    // Dispatch commission earned event
                    if ($transaction->commission_amount > 0 && $user->primary_campaign_id) {
                        $campaign = $user->primaryCampaign;
                        if ($campaign) {
                            CommissionEarned::dispatch($campaign, (float) $transaction->commission_amount);
                        }
                    }

                    return redirect()->route('coupons.index')->with('success', 'Debug mode: Coupon redeemed without payment.');
                }

                // 6. Initiate payment order
                $order = $this->paymentManager->driver()->createOrder($transaction);
                $transaction->update(['payment_id' => $order['id']]);

                return response()->json([
                    'order' => $order,
                    'transaction_id' => $transaction->id,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function verifyPayment(Request $request, Transaction $transaction)
    {
        $gateway = $this->paymentManager->driver($transaction->payment_gateway);

        if ($gateway->verifyPayment($request->all())) {
            DB::transaction(function () use ($transaction) {
                $transaction->update(['payment_status' => 'paid']);

                $user = $transaction->user;

                // Complete coupon redemption using the coupon linked to this transaction
                if ($transaction->coupon_id) {
                    $coupon = DiscountCoupon::find($transaction->coupon_id);
                    if ($coupon) {
                        $this->redemptionService->redeemCoupon($user, $coupon, $transaction, [
                            'original_bill_amount' => (float) $transaction->original_bill_amount,
                            'discount_amount' => (float) $transaction->discount_amount,
                            'platform_fee' => (float) $transaction->platform_fee,
                            'gst_amount' => (float) $transaction->gst_amount,
                            'total_paid' => (float) $transaction->total_amount,
                        ]);
                    }
                }

                // Award stamps based on bill amount
                $this->stampService->awardStampsForBill($transaction);

                // Dispatch commission earned event for bounty tracking
                if ($transaction->commission_amount > 0 && $user->primary_campaign_id) {
                    $campaign = $user->primaryCampaign;
                    if ($campaign) {
                        CommissionEarned::dispatch($campaign, (float) $transaction->commission_amount);
                    }
                }
            });

            return redirect()->route('coupons.index')->with('success', 'Payment successful and coupon redeemed!');
        }

        return redirect()->route('coupons.index')->with('error', 'Payment verification failed.');
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DiscountType;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionType;
use App\Events\CommissionEarned;
use App\Http\Controllers\Controller;
use App\Http\Requests\RedeemCouponRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Http\Resources\DiscountCouponResource;
use App\Http\Resources\TransactionResource;
use App\Models\CouponRedemption;
use App\Models\DiscountCoupon;
use App\Models\MerchantLocation;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Services\CouponRedemptionService;
use App\Services\Payments\PaymentManager;
use App\Services\StampService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @tags Coupons
 */
class CouponController extends Controller
{
    public function __construct(
        protected CouponRedemptionService $redemptionService,
        protected PaymentManager $paymentManager,
        protected StampService $stampService,
    ) {}

    /**
     * List coupons
     *
     * Returns a paginated list of active coupons segmented by user plan, category,
     * and merchant location. Includes eligibility info, remaining usage, and plan limits.
     *
     * @queryParam category_id int Filter by coupon category ID.
     * @queryParam merchant_location_id int Filter by merchant location ID.
     * @queryParam per_page int Items per page (default: 50, max: 100).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->effectiveSubscription();
        $planId = $subscription?->plan_id;
        $plan = $planId ? SubscriptionPlan::find($planId) : null;

        // Fetch accessible category IDs for the user's plan
        $accessibleCategoryIds = $plan
            ? $plan->couponCategories()->pluck('coupon_categories.id')->toArray()
            : [];

        $merchantLocationId = $request->input('merchant_location_id');

        // Build base query — all active coupons relevant to the context
        $query = DiscountCoupon::query()
            ->active()
            ->with(['merchantLocation.merchant', 'category.subscriptionPlans'])
            ->withCount('redemptions');

        // If merchant location specified, get coupons for that location AND platform coupons
        if ($merchantLocationId) {
            $query->where(function ($q) use ($merchantLocationId) {
                $q->where('merchant_location_id', $merchantLocationId)
                    ->orWhereNull('merchant_location_id');
            });
        }

        if ($request->input('category_id')) {
            $query->where('coupon_category_id', $request->input('category_id'));
        }

        $allCoupons = $query->latest()
            ->take(min((int) $request->input('per_page', 50), 100))
            ->get();

        // Calculate user's remaining plan limits
        $usedBillsCount = 0;
        $usedRedeemAmount = 0.0;

        if ($subscription && $subscription->id) {
            $subscriptionStart = $subscription->created_at;
            $usedBillsCount = CouponRedemption::where('user_id', $user->id)
                ->where('created_at', '>=', $subscriptionStart)
                ->count();
            $usedRedeemAmount = (float) CouponRedemption::where('user_id', $user->id)
                ->where('created_at', '>=', $subscriptionStart)
                ->sum('discount_applied');
        }

        $remainingBills = $plan ? max(0, $plan->max_discounted_bills - $usedBillsCount) : 0;
        $remainingRedeemAmount = $plan ? max(0, (float) $plan->max_redeemable_amount - $usedRedeemAmount) : 0;

        // Segment coupons
        $planCoupons = [];
        $storeCoupons = [];
        $otherCoupons = [];

        foreach ($allCoupons as $coupon) {
            $isEligible = ! empty($accessibleCategoryIds) && in_array($coupon->coupon_category_id, $accessibleCategoryIds);
            $isStoreCoupon = $merchantLocationId && $coupon->merchant_location_id == $merchantLocationId;

            // Compute remaining usage for this user
            $userRedemptions = $coupon->redemptions()->where('user_id', $user->id)->count();
            $remainingUsage = max(0, ($coupon->usage_per_user ?? 1) - $userRedemptions);

            // Check global usage limit
            $globalRemaining = null;
            if ($coupon->usage_limit !== null) {
                $globalRemaining = max(0, $coupon->usage_limit - $coupon->redemptions_count);
                if ($globalRemaining === 0) {
                    $remainingUsage = 0;
                }
            }

            $coupon->setAttribute('is_eligible', $isEligible);
            $coupon->setAttribute('remaining_usage', $remainingUsage);
            $coupon->setAttribute('user_redemptions_count', $userRedemptions);

            if (! $isEligible) {
                $cheapestPlan = $coupon->category?->subscriptionPlans()
                    ->orderBy('price', 'asc')
                    ->first();
                $coupon->setAttribute('required_plan', $cheapestPlan ? [
                    'id' => $cheapestPlan->id,
                    'name' => $cheapestPlan->name,
                    'price' => (float) $cheapestPlan->price,
                ] : null);
            }

            // Assign to segment — store coupons take priority, then plan, then other
            if ($isStoreCoupon) {
                $coupon->setAttribute('segment', 'store');
                $storeCoupons[] = $coupon;
            } elseif ($isEligible) {
                $coupon->setAttribute('segment', 'plan');
                $planCoupons[] = $coupon;
            } else {
                $coupon->setAttribute('segment', 'other');
                $otherCoupons[] = $coupon;
            }
        }

        return response()->json([
            'data' => [
                'plan_coupons' => DiscountCouponResource::collection(collect($planCoupons)),
                'store_coupons' => DiscountCouponResource::collection(collect($storeCoupons)),
                'other_coupons' => DiscountCouponResource::collection(collect($otherCoupons)),
            ],
            'meta' => [
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'max_discounted_bills' => $plan->max_discounted_bills,
                    'max_redeemable_amount' => (float) $plan->max_redeemable_amount,
                ] : null,
                'usage' => [
                    'bills_used' => $usedBillsCount,
                    'bills_remaining' => $remainingBills,
                    'discount_used' => round($usedRedeemAmount, 2),
                    'discount_remaining' => round($remainingRedeemAmount, 2),
                ],
                'platform_fee' => (float) config('app.platform_fee', 10),
                'platform_fee_type' => config('app.platform_fee_type', 'fixed'),
                'gst_rate' => (float) config('app.gst_rate', 18),
            ],
        ]);
    }

    /**
     * Show coupon
     *
     * Returns detailed information about a specific coupon.
     */
    public function show(DiscountCoupon $coupon): DiscountCouponResource
    {
        $coupon->load(['merchantLocation.merchant', 'category']);

        $user = request()->user();
        $subscription = $user->effectiveSubscription();
        $planId = $subscription?->plan_id;
        $coupon->setAttribute('is_eligible', $planId ? $coupon->isEligibleForPlan($planId) : false);

        $userRedemptions = $coupon->redemptions()->where('user_id', $user->id)->count();
        $coupon->setAttribute('remaining_usage', max(0, ($coupon->usage_per_user ?? 1) - $userRedemptions));

        return new DiscountCouponResource($coupon);
    }

    /**
     * Calculate coupon redemption
     *
     * Calculates the bill breakdown (discount, fees, final amount) without creating
     * any transaction. Use this for preview before payment. Accepts coupon_id or coupon_code.
     *
     * @bodyParam bill_amount numeric required The original bill amount. Example: 500
     * @bodyParam merchant_location_id int required The merchant location ID. Example: 1
     * @bodyParam coupon_id int optional The coupon ID to apply. Example: 5
     * @bodyParam coupon_code string optional The coupon code to apply.
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'bill_amount' => 'required|numeric|min:0',
            'merchant_location_id' => 'required|exists:merchant_locations,id',
            'coupon_id' => 'nullable|exists:discount_coupons,id',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $amount = (float) $request->input('bill_amount');

        $platformFee = (float) config('app.platform_fee', 10);
        if (config('app.platform_fee_type') === 'percentage') {
            $platformFee = ($amount * $platformFee) / 100;
        }
        $gstRate = (float) config('app.gst_rate', 18);
        $gstAmount = round(($platformFee * $gstRate) / 100, 2);

        $discountAmount = 0.0;
        $couponDiscount = 0.0;
        $coupon = null;
        $warnings = [];

        // Resolve coupon by ID or code
        if ($request->filled('coupon_id')) {
            $coupon = DiscountCoupon::active()->find($request->input('coupon_id'));
        } elseif ($request->filled('coupon_code')) {
            $coupon = DiscountCoupon::active()->where('code', $request->input('coupon_code'))->first();
            if (! $coupon) {
                $warnings[] = 'Invalid or expired coupon code.';
            }
        }

        if ($coupon) {
            // Check plan eligibility
            $subscription = $user->effectiveSubscription();
            $planId = $subscription?->plan_id;
            if ($planId && ! $coupon->isEligibleForPlan($planId)) {
                $cheapestPlan = $coupon->category?->subscriptionPlans()->orderBy('price', 'asc')->first();
                $warnings[] = $cheapestPlan
                    ? "This coupon requires the {$cheapestPlan->name} plan or higher."
                    : 'Your plan does not have access to this coupon.';
                $coupon = null; // Don't apply discount
            }
        }

        if ($coupon) {
            // Check min order value
            if ($coupon->min_order_value && $amount < (float) $coupon->min_order_value) {
                $warnings[] = "Minimum order value is ₹{$coupon->min_order_value}.";
                $coupon = null;
            }
        }

        if ($coupon) {
            // Check per-user usage limit
            $userRedemptions = $coupon->redemptions()->where('user_id', $user->id)->count();
            if ($userRedemptions >= ($coupon->usage_per_user ?? 1)) {
                $warnings[] = 'You have already used this coupon the maximum number of times.';
                $coupon = null;
            }
        }

        if ($coupon) {
            // Check global usage limit
            if ($coupon->usage_limit !== null) {
                $totalRedemptions = $coupon->redemptions()->count();
                if ($totalRedemptions >= $coupon->usage_limit) {
                    $warnings[] = 'This coupon has reached its usage limit.';
                    $coupon = null;
                }
            }
        }

        if ($coupon) {
            // Check plan-level limits
            $subscription = $user->effectiveSubscription();
            if ($subscription && $subscription->id) {
                $plan = SubscriptionPlan::find($subscription->plan_id);
                if ($plan) {
                    $subscriptionStart = $subscription->created_at;
                    $usedBills = CouponRedemption::where('user_id', $user->id)
                        ->where('created_at', '>=', $subscriptionStart)
                        ->count();
                    if ($usedBills >= $plan->max_discounted_bills) {
                        $warnings[] = "You have used all {$plan->max_discounted_bills} discounted bills for your plan.";
                        $coupon = null;
                    }
                }
            }
        }

        if ($coupon) {
            if ($coupon->discount_type === DiscountType::Fixed) {
                $discountAmount = (float) $coupon->discount_value;
            } else {
                $discountAmount = ($amount * (float) $coupon->discount_value) / 100;
            }

            if ($coupon->max_discount_amount) {
                $discountAmount = min($discountAmount, (float) $coupon->max_discount_amount);
            }

            // Check plan-level max redeemable amount
            $subscription = $user->effectiveSubscription();
            if ($subscription && $subscription->id) {
                $plan = SubscriptionPlan::find($subscription->plan_id);
                if ($plan && $plan->max_redeemable_amount > 0) {
                    $usedDiscount = (float) CouponRedemption::where('user_id', $user->id)
                        ->where('created_at', '>=', $subscription->created_at)
                        ->sum('discount_applied');
                    $remainingDiscount = max(0, (float) $plan->max_redeemable_amount - $usedDiscount);
                    if ($discountAmount > $remainingDiscount) {
                        $discountAmount = $remainingDiscount;
                        if ($remainingDiscount <= 0) {
                            $warnings[] = 'You have reached your maximum redeemable discount for this plan period.';
                        } else {
                            $warnings[] = "Discount capped at ₹" . round($remainingDiscount, 2) . ' (remaining plan limit).';
                        }
                    }
                }
            }

            $couponDiscount = round($discountAmount, 2);
        }

        $finalBillAfterDiscount = max(0, $amount - $discountAmount);
        $grandTotal = round($finalBillAfterDiscount + $platformFee + $gstAmount, 2);

        // Warn if store share would be negative (bill too low for fees)
        $storeShare = $finalBillAfterDiscount - $platformFee - $gstAmount;
        if ($finalBillAfterDiscount > 0 && $storeShare < 0) {
            $warnings[] = 'Bill amount after discount is too low to cover platform fees. The store would receive zero.';
        }

        return response()->json([
            'original_amount' => $amount,
            'discount' => round($discountAmount, 2),
            'convenience_fee' => round($platformFee, 2),
            'gst' => $gstAmount,
            'final_amount' => $grandTotal,
            'coupon_discount' => $couponDiscount,
            'coupon_applied' => $coupon ? [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'title' => $coupon->title,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float) $coupon->discount_value,
            ] : null,
            'warnings' => $warnings,
        ]);
    }

    /**
     * Redeem coupon
     *
     * Initiates a coupon redemption by creating a Razorpay order. The client must
     * complete the payment using the returned order details, then call verify-payment.
     * For zero-amount transactions (full discount), payment is auto-completed without Razorpay.
     *
     * @response 200 { "order": { "id": "order_xxx" }, "transaction_id": 1 }
     * @response 422 { "error": "Payment creation failed" }
     */
    public function redeem(RedeemCouponRequest $request, DiscountCoupon $coupon): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // Set primary campaign if not already set
        if (! $user->primary_campaign_id && ! empty($validated['campaign_id'])) {
            $user->update(['primary_campaign_id' => $validated['campaign_id']]);
            $user->refresh();
        }

        // ── Validation: Coupon must be active ──
        if (! $coupon->is_active || ($coupon->starts_at && $coupon->starts_at->isFuture()) ||
            ($coupon->expires_at && $coupon->expires_at->isPast())) {
            return response()->json(['error' => 'This coupon is no longer active or has expired.'], 422);
        }

        // ── Validation: Plan eligibility ──
        $subscription = $user->effectiveSubscription();
        $planId = $subscription?->plan_id;
        if (! $planId || ! $coupon->isEligibleForPlan($planId)) {
            return response()->json(['error' => 'Your subscription plan does not have access to this coupon.'], 403);
        }

        $merchantLocation = MerchantLocation::with('merchant')->findOrFail($validated['merchant_location_id']);
        $amount = (float) $validated['amount'];

        // ── Validation: Min order value ──
        if ($coupon->min_order_value && $amount < (float) $coupon->min_order_value) {
            return response()->json([
                'error' => "Minimum bill amount for this coupon is ₹{$coupon->min_order_value}.",
            ], 422);
        }

        // ── Validation: Per-user usage limit ──
        $userRedemptions = $coupon->redemptions()->where('user_id', $user->id)->count();
        if ($userRedemptions >= ($coupon->usage_per_user ?? 1)) {
            return response()->json([
                'error' => 'You have already used this coupon the maximum number of times.',
            ], 422);
        }

        // ── Validation: Global usage limit ──
        if ($coupon->usage_limit !== null) {
            $totalRedemptions = $coupon->redemptions()->count();
            if ($totalRedemptions >= $coupon->usage_limit) {
                return response()->json(['error' => 'This coupon has reached its usage limit.'], 422);
            }
        }

        // ── Validation: Plan-level bill limit ──
        $plan = $planId ? SubscriptionPlan::find($planId) : null;
        if ($plan && $subscription->id) {
            $subscriptionStart = $subscription->created_at;
            $usedBills = CouponRedemption::where('user_id', $user->id)
                ->where('created_at', '>=', $subscriptionStart)
                ->count();
            if ($usedBills >= $plan->max_discounted_bills) {
                return response()->json([
                    'error' => "You have used all {$plan->max_discounted_bills} discounted bills for your plan period.",
                ], 422);
            }
        }

        try {
            return DB::transaction(function () use ($user, $coupon, $merchantLocation, $amount, $plan, $subscription): JsonResponse {
                $platformFee = (float) config('app.platform_fee', 10);
                if (config('app.platform_fee_type') === 'percentage') {
                    $platformFee = ($amount * $platformFee) / 100;
                }
                $gstAmount = ($platformFee * (float) config('app.gst_rate', 18)) / 100;

                $discountAmount = 0.0;
                if ($coupon->discount_type === DiscountType::Fixed) {
                    $discountAmount = (float) $coupon->discount_value;
                } else {
                    $discountAmount = ($amount * (float) $coupon->discount_value) / 100;
                }

                if ($coupon->max_discount_amount) {
                    $discountAmount = min($discountAmount, (float) $coupon->max_discount_amount);
                }

                // ── Validation: Plan-level max redeemable amount ──
                if ($plan && $plan->max_redeemable_amount > 0 && $subscription->id) {
                    $usedDiscount = (float) CouponRedemption::where('user_id', $user->id)
                        ->where('created_at', '>=', $subscription->created_at)
                        ->sum('discount_applied');
                    $remainingDiscount = max(0, (float) $plan->max_redeemable_amount - $usedDiscount);
                    $discountAmount = min($discountAmount, $remainingDiscount);
                }

                $finalBillAfterDiscount = max(0, $amount - $discountAmount);
                $grandTotal = $finalBillAfterDiscount + $platformFee + $gstAmount;
                $commissionAmount = ($finalBillAfterDiscount * $merchantLocation->commission_percentage) / 100;

                $idempotencyKey = 'coupon_' . $user->id . '_' . $coupon->id . '_' . Str::uuid();

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
                    'payment_status' => PaymentStatus::Pending,
                    'type' => TransactionType::CouponRedemption,
                    'idempotency_key' => $idempotencyKey,
                    'commission_amount' => $commissionAmount,
                ]);

                // ── Zero-amount transaction: skip Razorpay ──
                if ($grandTotal <= 0) {
                    $transaction->update([
                        'payment_status' => PaymentStatus::Paid,
                        'payment_id' => 'zero_amount_' . $transaction->id,
                    ]);

                    $this->redemptionService->redeemCoupon($user, $coupon, $transaction, [
                        'original_bill_amount' => $amount,
                        'discount_amount' => $discountAmount,
                        'platform_fee' => $platformFee,
                        'gst_amount' => $gstAmount,
                        'total_paid' => $grandTotal,
                    ]);

                    $this->stampService->awardStampsForCouponRedemption($transaction, isset($validated['campaign_id']) ? (int) $validated['campaign_id'] : null);

                    return response()->json([
                        'order' => null,
                        'transaction_id' => $transaction->id,
                        'zero_amount' => true,
                        'message' => 'Coupon redeemed successfully — no payment required.',
                        'transaction' => new TransactionResource($transaction->load(['coupon', 'merchantLocation', 'stamps'])),
                    ]);
                }

                $order = $this->paymentManager->driver()->createOrder($transaction);
                $transaction->update(['razorpay_order_id' => $order['id']]);

                if (! empty($order['transfers'][0]['id'])) {
                    $transaction->update(['transfer_id' => $order['transfers'][0]['id']]);
                }

                return response()->json([
                    'order' => $order,
                    'transaction_id' => $transaction->id,
                    'zero_amount' => false,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Verify coupon payment
     *
     * Verifies the Razorpay payment for a coupon redemption. On success, the coupon
     * is redeemed and stamps are awarded.
     *
     * @response 200 { "message": "Payment verified and coupon redeemed successfully.", "transaction": {} }
     * @response 422 { "error": "Payment verification failed." }
     */
    public function verifyPayment(VerifyPaymentRequest $request): JsonResponse
    {
        $transaction = Transaction::where('razorpay_order_id', $request->input('razorpay_order_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Prevent double-verification
        if ($transaction->payment_status === PaymentStatus::Paid) {
            return response()->json([
                'message' => 'Payment already verified.',
                'transaction' => new TransactionResource($transaction->load(['coupon', 'merchantLocation', 'stamps'])),
            ]);
        }

        $gateway = $this->paymentManager->driver($transaction->payment_gateway);

        if (! $gateway->verifyPayment($request->all())) {
            return response()->json(['error' => 'Payment verification failed.'], 422);
        }

        $campaignId = $request->input('campaign_id') ? (int) $request->input('campaign_id') : null;

        DB::transaction(function () use ($transaction, $request, $campaignId): void {
            $transaction->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_id' => $request->input('razorpay_payment_id'),
            ]);

            $user = $transaction->user;

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

            $this->stampService->awardStampsForCouponRedemption($transaction, $campaignId);

            if ($transaction->commission_amount > 0 && $user->primary_campaign_id) {
                $campaign = $user->primaryCampaign;
                if ($campaign) {
                    CommissionEarned::dispatch($campaign, (float) $transaction->commission_amount);
                }
            }
        });

        return response()->json([
            'message' => 'Payment verified and coupon redeemed successfully.',
            'transaction' => new TransactionResource($transaction->load(['coupon', 'merchantLocation', 'stamps'])),
        ]);
    }

    /**
     * Pay without coupon
     *
     * Create a payment order without applying any coupon.
     * Calculates platform fee and GST on the bill amount.
     */
    public function payWithoutCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchant_location_id' => 'required|integer|exists:merchant_locations,id',
            'amount' => 'required|numeric|min:0.01',
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
        ]);

        $user = $request->user();
        $merchantLocation = MerchantLocation::with('merchant')->findOrFail($validated['merchant_location_id']);
        $amount = (float) $validated['amount'];
        $campaignId = isset($validated['campaign_id']) ? (int) $validated['campaign_id'] : null;

        try {
            return DB::transaction(function () use ($user, $merchantLocation, $amount, $campaignId): JsonResponse {
                $platformFee = (float) config('app.platform_fee', 10);
                if (config('app.platform_fee_type') === 'percentage') {
                    $platformFee = ($amount * $platformFee) / 100;
                }
                $gstAmount = ($platformFee * (float) config('app.gst_rate', 18)) / 100;

                $grandTotal = $amount + $platformFee + $gstAmount;
                $commissionAmount = ($amount * $merchantLocation->commission_percentage) / 100;

                $idempotencyKey = 'nocoupon_' . $user->id . '_' . Str::uuid();

                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'coupon_id' => null, // No coupon applied
                    'merchant_location_id' => $merchantLocation->id,
                    'original_bill_amount' => $amount,
                    'discount_amount' => 0,
                    'amount' => $amount,
                    'platform_fee' => $platformFee,
                    'gst_amount' => $gstAmount,
                    'total_amount' => $grandTotal,
                    'payment_gateway' => $this->paymentManager->getDefaultDriver(),
                    'payment_status' => PaymentStatus::Pending,
                    'type' => TransactionType::CouponRedemption,
                    'idempotency_key' => $idempotencyKey,
                    'commission_amount' => $commissionAmount,
                ]);

                // ── Zero-amount transaction: skip Razorpay ──
                if ($grandTotal <= 0) {
                    $transaction->update([
                        'payment_status' => PaymentStatus::Paid,
                        'payment_id' => 'zero_amount_' . $transaction->id,
                    ]);

                    $this->stampService->awardStampsForCouponRedemption($transaction, $campaignId);

                    return response()->json([
                        'order' => null,
                        'transaction_id' => $transaction->id,
                        'zero_amount' => true,
                        'message' => 'Payment processed successfully — no payment required.',
                        'transaction' => new TransactionResource($transaction->load(['coupon', 'merchantLocation', 'stamps'])),
                    ]);
                }

                $order = $this->paymentManager->driver()->createOrder($transaction);
                $transaction->update(['razorpay_order_id' => $order['id']]);

                if (! empty($order['transfers'][0]['id'])) {
                    $transaction->update(['transfer_id' => $order['transfers'][0]['id']]);
                }

                return response()->json([
                    'order' => $order,
                    'transaction_id' => $transaction->id,
                    'zero_amount' => false,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process payment: ' . $e->getMessage(),
            ], 500);
        }
    }
}

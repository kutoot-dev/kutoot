<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Dashboard
 */
class DashboardController extends Controller
{
    /**
     * Get dashboard
     *
     * Returns the user's dashboard data including subscription plan info,
     * stamp statistics, coupon usage, and eligible campaigns.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->effectiveSubscription();
        $plan = $subscription ? SubscriptionPlan::find($subscription->plan_id) : null;

        $stampsCount = $user->stamps()->count();
        $totalCouponsUsed = $user->couponRedemptions()->count();
        $totalDiscountRedeemed = (float) $user->couponRedemptions()->sum('discount_applied');

        $remainingBills = $plan
            ? max(0, $plan->max_discounted_bills - $totalCouponsUsed)
            : 0;

        $remainingRedeemAmount = $plan
            ? max(0, (float) $plan->max_redeemable_amount - $totalDiscountRedeemed)
            : 0;

        $eligibleCampaigns = $plan
            ? $plan->campaigns()
                ->where('is_active', true)
                ->where('status', 'active')
                ->get(['campaigns.id', 'reward_name'])
                ->map(fn (Campaign $c) => [
                    'id' => $c->id,
                    'reward_name' => $c->reward_name,
                ])
            : collect();

        return response()->json([
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'gender' => $user->gender,
                    'country' => $user->country,
                    'state' => $user->state,
                    'city' => $user->city,
                    'pin_code' => $user->pin_code,
                    'full_address' => $user->full_address,
                    'profile_picture_url' => $user->profile_picture_url,
                    'created_at' => $user->created_at->toISOString(),
                ],
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'is_default' => $plan->is_default,
                    'stamps_on_purchase' => $plan->stamps_on_purchase,
                    'stamp_denomination' => (float) $plan->stamp_denomination,
                    'stamps_per_denomination' => $plan->stamps_per_denomination,
                    'max_discounted_bills' => $plan->max_discounted_bills,
                    'max_redeemable_amount' => (float) $plan->max_redeemable_amount,
                    'duration_days' => $plan->duration_days,
                    'purchased_at' => $subscription->created_at?->toISOString(),
                    'expires_at' => $subscription->expires_at?->toISOString(),
                    'days_remaining' => $subscription->expires_at
                        ? (int) max(0, now()->diffInDays($subscription->expires_at, false))
                        : null,
                ] : null,
                'primary_campaign' => $user->primaryCampaign ? [
                    'id' => $user->primaryCampaign->id,
                    'reward_name' => $user->primaryCampaign->reward_name,
                ] : null,
                'eligible_campaigns' => $eligibleCampaigns,
                'stats' => [
                    'stamps_count' => $stampsCount,
                    'total_coupons_used' => $totalCouponsUsed,
                    'total_discount_redeemed' => $totalDiscountRedeemed,
                    'remaining_bills' => $remainingBills,
                    'remaining_redeem_amount' => $remainingRedeemAmount,
                ],
            ],
        ]);
    }
}

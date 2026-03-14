<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $subscription = $user->effectiveSubscription();
        $plan = $subscription ? SubscriptionPlan::find($subscription->plan_id) : null;

        $stampsCount = $user->stamps()->count();
        $totalCouponsUsed = $subscription ? (int) $subscription->bills_used : 0;
        $totalDiscountRedeemed = $subscription ? (float) $subscription->amount_redeemed : 0.0;

        $remainingBills = $plan
            ? max(0, $plan->max_discounted_bills - $totalCouponsUsed)
            : 0;

        $remainingRedeemAmount = $plan
            ? max(0, (float) $plan->max_redeemable_amount - $totalDiscountRedeemed)
            : 0;

        // Campaigns the user is eligible for under their current plan
        $eligibleCampaigns = $plan
            ? $plan->campaigns()
                ->where('is_active', true)
                ->where('status', 'active')
                ->get(['campaigns.id', 'reward_name', 'category_id'])
                ->map(fn (Campaign $c) => [
                    'id' => $c->id,
                    'reward_name' => $c->reward_name,
                ])
            : collect();

        return Inertia::render('Dashboard', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->format('M d, Y'),
            ],
            'plan' => $plan ? [
                'name' => $plan->name,
                'is_default' => $plan->is_default,
                'stamps_on_purchase' => $plan->stamps_on_purchase,
                'stamp_denomination' => (float) $plan->stamp_denomination,
                'stamps_per_denomination' => $plan->stamps_per_denomination,
                'max_discounted_bills' => $plan->max_discounted_bills,
                'max_redeemable_amount' => (float) $plan->max_redeemable_amount,
                'duration_days' => $plan->duration_days,
                'is_lifetime' => (bool) $plan->is_default,
                'purchased_at' => $subscription->created_at?->format('M d, Y'),
                'expires_at' => $plan->is_default ? null : $subscription->expires_at?->format('M d, Y'),
                'days_remaining' => $plan->is_default ? null : app(\App\Services\SubscriptionService::class)
                    ->calculateDaysRemaining($subscription->expires_at),
            ] : null,
            'primaryCampaign' => $user->primaryCampaign ? [
                'id' => $user->primaryCampaign->id,
                'reward_name' => $user->primaryCampaign->reward_name,
            ] : null,
            'eligibleCampaigns' => $eligibleCampaigns,
            'stats' => [
                'stamps_count' => $stampsCount,
                'total_coupons_used' => $totalCouponsUsed,
                'total_discount_redeemed' => $totalDiscountRedeemed,
                'remaining_bills' => $remainingBills,
                'remaining_redeem_amount' => $remainingRedeemAmount,
            ],
        ]);
    }
}

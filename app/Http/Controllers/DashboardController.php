<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(Request $request): Response
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

        $recentTransactions = $user->transactions()
            ->with(['coupon:id,title', 'merchantLocation:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'amount' => (float) $t->amount,
                'total_amount' => (float) $t->total_amount,
                'payment_status' => $t->payment_status,
                'coupon_title' => $t->coupon?->title,
                'location_name' => $t->merchantLocation?->name,
                'created_at' => $t->created_at->diffForHumans(),
            ]);

        $recentRedemptions = $user->couponRedemptions()
            ->with(['coupon:id,title', 'transaction:id,amount'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'coupon_title' => $r->coupon?->title,
                'discount_applied' => (float) $r->discount_applied,
                'bill_amount' => $r->transaction ? (float) $r->transaction->amount : null,
                'created_at' => $r->created_at->diffForHumans(),
            ]);

        $activityLogs = Activity::causedBy($user)
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                'subject_type' => class_basename($a->subject_type ?? ''),
                'event' => $a->event,
                'created_at' => $a->created_at->diffForHumans(),
            ]);

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
                'stamps_per_100' => $plan->stamps_per_100,
                'max_discounted_bills' => $plan->max_discounted_bills,
                'max_redeemable_amount' => (float) $plan->max_redeemable_amount,
                'duration_days' => $plan->duration_days,
                'purchased_at' => $subscription->created_at?->format('M d, Y'),
                'expires_at' => $subscription->expires_at?->format('M d, Y'),
                'days_remaining' => $subscription->expires_at
                    ? (int) max(0, now()->diffInDays($subscription->expires_at, false))
                    : null,
            ] : null,
            'primaryCampaign' => $user->primaryCampaign?->name,
            'stats' => [
                'stamps_count' => $stampsCount,
                'total_coupons_used' => $totalCouponsUsed,
                'total_discount_redeemed' => $totalDiscountRedeemed,
                'remaining_bills' => $remainingBills,
                'remaining_redeem_amount' => $remainingRedeemAmount,
            ],
            'recentTransactions' => $recentTransactions,
            'recentRedemptions' => $recentRedemptions,
            'activityLogs' => $activityLogs,
        ]);
    }
}

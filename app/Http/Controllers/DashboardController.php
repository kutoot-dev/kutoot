<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\ActivityLogHumanizer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __construct(
        protected ActivityLogHumanizer $humanizer,
    ) {}

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

        $recentActivity = $user->transactions()
            ->with(['coupon:id,title', 'merchantLocation:id,branch_name', 'couponRedemption', 'stamps'])
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'coupon_title' => $t->coupon?->title,
                'location_name' => $t->merchantLocation?->branch_name,
                'original_bill_amount' => (float) ($t->original_bill_amount ?: $t->amount),
                'discount_amount' => (float) ($t->couponRedemption?->discount_applied ?? $t->discount_amount ?? 0),
                'platform_fee' => (float) ($t->couponRedemption?->platform_fee ?? 0),
                'gst_amount' => (float) ($t->couponRedemption?->gst_amount ?? 0),
                'total_paid' => (float) ($t->couponRedemption?->total_paid ?? $t->total_amount),
                'stamps_earned' => $t->stamps->count(),
                'payment_status' => $t->payment_status,
                'created_at' => $t->created_at->diffForHumans(),
            ]);

        $stamps = $user->stamps()
            ->with(['campaign:id,reward_name', 'transaction:id,amount,original_bill_amount'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'source' => $s->source->getLabel(),
                'campaign_name' => $s->campaign?->reward_name,
                'bill_amount' => (float) ($s->transaction?->original_bill_amount ?: $s->transaction?->amount ?? 0),
                'created_at' => $s->created_at->diffForHumans(),
            ]);

        $activityLogs = Activity::causedBy($user)
            ->with('subject')
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'description' => $this->humanizer->humanize($a),
                'subject_type' => class_basename($a->subject_type ?? ''),
                'event' => $a->event,
                'icon' => $this->humanizer->icon($a->event ?? 'updated'),
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
            'primaryCampaign' => $user->primaryCampaign?->reward_name,
            'stats' => [
                'stamps_count' => $stampsCount,
                'total_coupons_used' => $totalCouponsUsed,
                'total_discount_redeemed' => $totalDiscountRedeemed,
                'remaining_bills' => $remainingBills,
                'remaining_redeem_amount' => $remainingRedeemAmount,
            ],
            'recentActivity' => $recentActivity,
            'stamps' => $stamps,
            'activityLogs' => $activityLogs,
        ]);
    }
}

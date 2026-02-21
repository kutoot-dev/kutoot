<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Fetch subscription/plan purchase transactions
        $subscriptionTransactions = $user->transactions()
            ->where('type', TransactionType::PlanPurchase)
            ->with('subscription')
            ->latest()
            ->paginate(20, ['*'], 'sub_page');

        // Fetch coupon redemption transactions
        $couponTransactions = $user->transactions()
            ->where('type', TransactionType::CouponRedemption)
            ->with(['coupon:id,title', 'merchantLocation:id,branch_name', 'couponRedemption'])
            ->latest()
            ->paginate(20, ['*'], 'coupon_page');

        $subscriptionData = $subscriptionTransactions->map(fn ($t) => [
            'id' => $t->id,
            'type' => 'subscription',
            'plan_name' => $t->subscription?->plan?->name ?? 'N/A',
            'amount' => (float) $t->original_bill_amount,
            'gst_amount' => (float) ($t->gst_amount ?? 0),
            'total_amount' => (float) $t->total_amount,
            'payment_status' => $t->payment_status->getLabel(),
            'payment_method' => $t->payment_gateway,
            'payment_id' => $t->payment_id,
            'created_at' => $t->created_at->format('M d, Y H:i'),
            'created_at_human' => $t->created_at->diffForHumans(),
        ]);

        $couponData = $couponTransactions->map(fn ($t) => [
            'id' => $t->id,
            'type' => 'coupon',
            'coupon_title' => $t->coupon?->title ?? 'N/A',
            'merchant_location' => $t->merchantLocation?->branch_name ?? 'N/A',
            'bill_amount' => (float) $t->original_bill_amount,
            'discount_applied' => (float) ($t->couponRedemption?->discount_applied ?? $t->discount_amount ?? 0),
            'platform_fee' => (float) ($t->couponRedemption?->platform_fee ?? 0),
            'gst_amount' => (float) ($t->couponRedemption?->gst_amount ?? 0),
            'total_paid' => (float) ($t->couponRedemption?->total_paid ?? $t->total_amount),
            'payment_status' => $t->payment_status->getLabel(),
            'payment_method' => $t->payment_gateway,
            'payment_id' => $t->payment_id,
            'created_at' => $t->created_at->format('M d, Y H:i'),
            'created_at_human' => $t->created_at->diffForHumans(),
        ]);

        return Inertia::render('Transactions/Index', [
            'subscriptionTransactions' => [
                'data' => $subscriptionData,
                'links' => $subscriptionTransactions->render(),
                'total' => $subscriptionTransactions->total(),
            ],
            'couponTransactions' => [
                'data' => $couponData,
                'links' => $couponTransactions->render(),
                'total' => $couponTransactions->total(),
            ],
        ]);
    }
}

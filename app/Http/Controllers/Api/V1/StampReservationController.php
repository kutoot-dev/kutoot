<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
use App\Enums\StampStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmReservationRequest;
use App\Http\Requests\ReserveStampRequest;
use App\Http\Resources\StampResource;
use App\Http\Resources\TransactionResource;
use App\Models\Stamp;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\TaxCalculator;
use App\Services\StampReservationService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @tags Stamp Reservations
 */
class StampReservationController extends Controller
{
    public function __construct(
        protected StampReservationService $reservationService,
        protected SubscriptionService $subscriptionService,
        protected PaymentManager $paymentManager,
        protected TaxCalculator $taxCalculator,
    ) {}

    /**
     * Reserve a stamp
     *
     * Atomically reserves a stamp for the authenticated user on the given campaign.
     * Uses database-level locking to prevent double-allocation. If the user already
     * has an active reservation for this campaign, the existing reservation is returned
     * (idempotent). Reservation expires after 5 minutes.
     *
     * @response 200 { "data": { "id": 1, "code": "CAMP-01-02-03", "status": "reserved", ... }, "remaining_seconds": 300 }
     * @response 422 { "error": "Campaign not found or is no longer active." }
     */
    public function reserve(ReserveStampRequest $request): JsonResponse
    {
        try {
            $stamp = $this->reservationService->reserve(
                $request->user(),
                (int) $request->validated('campaign_id'),
            );

            return response()->json([
                'data' => new StampResource($stamp),
                'remaining_seconds' => $stamp->remainingSeconds(),
                'expires_at' => $stamp->expires_at?->toISOString(),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }

    /**
     * Get reservation status
     *
     * Returns the current state of a stamp reservation, including remaining time.
     * If the reservation has expired at query time, its status is updated to expired.
     *
     * @response 200 { "data": { ... }, "remaining_seconds": 180, "is_active": true }
     * @response 403 { "error": "Unauthorized." }
     */
    public function show(Request $request, Stamp $stamp): JsonResponse
    {
        if ($stamp->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // Belt-and-suspenders: expire at query time
        if ($stamp->status === StampStatus::Reserved && $stamp->expires_at?->isPast()) {
            $stamp->update(['status' => StampStatus::Expired]);
            $stamp->refresh();
        }

        return response()->json([
            'data' => new StampResource($stamp->load('campaign')),
            'remaining_seconds' => $stamp->remainingSeconds(),
            'is_active' => $stamp->isReserved(),
        ]);
    }

    /**
     * Confirm a reservation with payment
     *
     * Verifies the Razorpay payment, creates a transaction record, and transitions
     * the stamp from Reserved → Used. If a plan_id is provided, the user's
     * subscription is also upgraded.
     *
     * @response 200 { "message": "Stamp confirmed!", "data": { ... }, "transaction": { ... } }
     * @response 409 { "error": "This reservation has expired. Please reserve a new stamp." }
     * @response 422 { "error": "Payment verification failed." }
     */
    public function confirm(ConfirmReservationRequest $request, Stamp $stamp): JsonResponse
    {
        $user = $request->user();

        if ($stamp->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // Check expiry before attempting payment verification
        if ($stamp->status === StampStatus::Reserved && $stamp->expires_at?->isPast()) {
            $stamp->update(['status' => StampStatus::Expired]);

            return response()->json([
                'error' => 'This reservation has expired. Please reserve a new stamp.',
            ], 409);
        }

        if ($stamp->status !== StampStatus::Reserved) {
            return response()->json([
                'error' => 'This stamp is not in a reserved state.',
            ], 422);
        }

        // ── Verify Razorpay payment ─────────────────────────────────
        $gateway = $this->paymentManager->driver();

        if (! $gateway->verifyPayment($request->validated())) {
            return response()->json(['error' => 'Payment verification failed.'], 422);
        }

        // ── Find or create the transaction ──────────────────────────
        $transaction = Transaction::where('razorpay_order_id', $request->input('razorpay_order_id'))
            ->where('user_id', $user->id)
            ->first();

        if ($transaction) {
            $transaction->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_id' => $request->input('razorpay_payment_id'),
            ]);
        } else {
            // Create transaction if one doesn't exist (e.g. free/direct flow)
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'coupon_id' => null,
                'original_bill_amount' => 0,
                'amount' => 0,
                'platform_fee' => 0,
                'gst_amount' => 0,
                'total_amount' => 0,
                'payment_gateway' => $this->paymentManager->getDefaultDriver(),
                'payment_id' => $request->input('razorpay_payment_id'),
                'razorpay_order_id' => $request->input('razorpay_order_id'),
                'payment_status' => PaymentStatus::Paid,
                'type' => TransactionType::PlanPurchase,
                'idempotency_key' => 'stamp_confirm_'.$stamp->id.'_'.Str::uuid(),
            ]);
        }

        // ── Confirm the stamp reservation ───────────────────────────
        try {
            $confirmedStamp = $this->reservationService->confirm($stamp, $transaction);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }

        // ── Optionally upgrade subscription ─────────────────────────
        $planId = $request->input('plan_id');
        $stampsAwarded = 0;
        $extraStamps = collect();

        if ($planId) {
            $plan = SubscriptionPlan::find($planId);
            if ($plan && ! $plan->is_default) {
                $this->subscriptionService->upgradePlan($user, $plan->id, $transaction);
                $extraStamps = $transaction->fresh()->stamps()->with('campaign')->get();
                $stampsAwarded = $extraStamps->count();
            }
        }

        return response()->json([
            'message' => 'Stamp confirmed successfully!',
            'data' => new StampResource($confirmedStamp),
            'transaction' => new TransactionResource($transaction->fresh()),
            'stamps_awarded' => $stampsAwarded,
            'extra_stamps' => StampResource::collection($extraStamps),
        ]);
    }

    /**
     * Cancel a reservation
     *
     * Releases the reserved stamp so it can no longer be claimed.
     * The stamp is marked as Expired.
     *
     * @response 200 { "message": "Reservation cancelled." }
     * @response 403 { "error": "Unauthorized." }
     */
    public function cancel(Request $request, Stamp $stamp): JsonResponse
    {
        if ($stamp->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        try {
            $this->reservationService->cancel($stamp);

            return response()->json(['message' => 'Reservation cancelled.']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Create a Razorpay order for the stamp reservation checkout.
     *
     * Called by the frontend when the user is ready to pay. Creates a pending
     * transaction and returns the Razorpay order details for the checkout popup.
     *
     * @response 200 { "order": { "id": "order_xxx", ... }, "transaction_id": 5 }
     */
    public function createOrder(Request $request, Stamp $stamp): JsonResponse
    {
        $user = $request->user();

        if ($stamp->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        if ($stamp->status !== StampStatus::Reserved || ($stamp->expires_at && $stamp->expires_at->isPast())) {
            return response()->json(['error' => 'Reservation is no longer active.'], 409);
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $planPrice = (float) $plan->price;

        if ($planPrice <= 0) {
            return response()->json(['error' => 'This plan is free. No payment required.'], 422);
        }

        $priceInPaise = (int) round($planPrice * 100);
        $taxType = config('app.plan_tax_type', 'exclusive');
        $taxBreakdown = $this->taxCalculator->calculatePlanTotal($priceInPaise, $taxType);

        $idempotencyKey = 'stamp_reserve_'.$user->id.'_'.$stamp->id.'_'.Str::uuid();

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
                'stamp_id' => $stamp->id,
            ]);
        } catch (\Exception $e) {
            $transaction->update(['payment_status' => PaymentStatus::Failed]);

            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}

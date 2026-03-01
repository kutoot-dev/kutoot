<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Events\CommissionEarned;
use App\Models\DiscountCoupon;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TransferFailedNotification;
use App\Services\CouponRedemptionService;
use App\Services\Payments\RazorpayGateway;
use App\Services\StampService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RazorpayWebhookController extends Controller
{
    public function __construct(
        protected RazorpayGateway $gateway,
        protected CouponRedemptionService $redemptionService,
        protected StampService $stampService,
        protected SubscriptionService $subscriptionService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');
        $secret = config('app.razorpay.webhook_secret');

        if (! $secret || ! $this->gateway->verifyWebhookSignature($payload, $signature, $secret)) {
            Log::warning('Razorpay Webhook: Invalid signature', [
                'signature' => $signature,
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? '';

        Log::info('Razorpay Webhook received', ['event' => $event]);

        return match ($event) {
            'payment.captured' => $this->handlePaymentCaptured($data),
            'payment.failed' => $this->handlePaymentFailed($data),
            'refund.created' => $this->handleRefundCreated($data),
            'transfer.failed' => $this->handleTransferFailed($data),
            default => response()->json(['status' => 'ignored']),
        };
    }

    private function handlePaymentCaptured(array $data): JsonResponse
    {
        $payment = $data['payload']['payment']['entity'] ?? [];
        $orderId = $payment['order_id'] ?? null;
        $paymentId = $payment['id'] ?? null;

        if (! $orderId || ! $paymentId) {
            return response()->json(['error' => 'Missing payment data'], 400);
        }

        $transaction = Transaction::where('razorpay_order_id', $orderId)->first();

        if (! $transaction) {
            Log::warning('Razorpay Webhook: Transaction not found for order', ['order_id' => $orderId]);

            return response()->json(['status' => 'transaction_not_found']);
        }

        // Idempotency: skip if already processed
        if ($transaction->payment_status === PaymentStatus::Paid || $transaction->payment_status === PaymentStatus::Completed) {
            Log::info('Razorpay Webhook: Payment already processed', ['order_id' => $orderId]);
            return response()->json(['status' => 'already_processed']);
        }

        DB::transaction(function () use ($transaction, $paymentId) {
            $transaction->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_id' => $paymentId,
            ]);

            // Store transfer ID from payment if available
            $this->storeTransferIdFromPayment($transaction, $paymentId);

            if ($transaction->type === TransactionType::PlanPurchase) {
                $this->processPlanPurchase($transaction);
            } else {
                $this->processCouponRedemption($transaction);
            }
        });

        Log::info('Razorpay Webhook: Payment processed successfully', [
            'order_id' => $orderId,
            'transaction_id' => $transaction->id,
            'type' => $transaction->type,
        ]);

        return response()->json(['status' => 'ok']);
    }

    private function handlePaymentFailed(array $data): JsonResponse
    {
        $payment = $data['payload']['payment']['entity'] ?? [];
        $orderId = $payment['order_id'] ?? null;

        if (! $orderId) {
            return response()->json(['error' => 'Missing order ID'], 400);
        }

        $transaction = Transaction::where('razorpay_order_id', $orderId)->first();

        if ($transaction && $transaction->payment_status === PaymentStatus::Pending) {
            $transaction->update(['payment_status' => PaymentStatus::Failed]);
            Log::info('Razorpay Webhook: Payment failed', ['transaction_id' => $transaction->id]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleRefundCreated(array $data): JsonResponse
    {
        $refund = $data['payload']['refund']['entity'] ?? [];
        $paymentId = $refund['payment_id'] ?? null;
        $refundId = $refund['id'] ?? null;

        if (! $paymentId) {
            return response()->json(['error' => 'Missing payment ID'], 400);
        }

        $transaction = Transaction::where('payment_id', $paymentId)->first();

        if ($transaction) {
            $transaction->update([
                'payment_status' => PaymentStatus::Refunded,
                'refund_id' => $refundId,
            ]);
            Log::info('Razorpay Webhook: Refund created', [
                'transaction_id' => $transaction->id,
                'refund_id' => $refundId,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleTransferFailed(array $data): JsonResponse
    {
        $transfer = $data['payload']['transfer']['entity'] ?? [];
        $transferId = $transfer['id'] ?? null;

        Log::error('Razorpay Webhook: Transfer failed', [
            'transfer_id' => $transferId,
            'data' => $transfer,
        ]);

        // Find transaction by transfer_id
        $transaction = $transferId
            ? Transaction::where('transfer_id', $transferId)->first()
            : null;

        // Notify admins
        $admins = User::role('Super Admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new TransferFailedNotification(
                transferId: $transferId ?? 'unknown',
                transactionId: $transaction?->id,
                errorMessage: $transfer['error']['description'] ?? 'Transfer to linked account failed',
            ));
        }

        return response()->json(['status' => 'ok']);
    }

    private function processPlanPurchase(Transaction $transaction): void
    {
        // Plan purchase - mark payment as completed via webhook as backup
        $user = $transaction->user;
        $activeSubscription = $user->activeSubscription;

        // If no active subscription yet, activate it via webhook (in case frontend callback fails)
        if (! $activeSubscription || $transaction->payment_status !== PaymentStatus::Completed) {
            try {
                $plan = SubscriptionPlan::find($transaction->plan_id);

                if ($plan) {
                    // Activate the plan upgrade
                    $this->subscriptionService->upgradePlan($user, $plan->id, $transaction, []);

                    // Mark as completed
                    $transaction->update(['payment_status' => PaymentStatus::Completed]);

                    Log::info('Razorpay Webhook: Plan purchase activated', [
                        'transaction_id' => $transaction->id,
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the webhook response
                Log::error('Razorpay Webhook: Plan purchase activation failed', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function processCouponRedemption(Transaction $transaction): void
    {
        $user = $transaction->user;

        // Complete coupon redemption
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

        // Award stamps
        $this->stampService->awardStampsForBill($transaction);

        // Dispatch commission earned event
        if ($transaction->commission_amount > 0 && $user->primary_campaign_id) {
            $campaign = $user->primaryCampaign;
            if ($campaign) {
                CommissionEarned::dispatch($campaign, (float) $transaction->commission_amount);
            }
        }
    }

    /**
     * Try to fetch and store the transfer ID from the payment.
     */
    private function storeTransferIdFromPayment(Transaction $transaction, string $paymentId): void
    {
        if ($transaction->transfer_id || $transaction->type !== TransactionType::CouponRedemption) {
            return;
        }

        try {
            $paymentData = $this->gateway->fetchPayment($paymentId);
            $items = $paymentData['items'] ?? [];

            if (! empty($items[0]['id'])) {
                $transaction->update(['transfer_id' => $items[0]['id']]);
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch transfer ID from payment', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

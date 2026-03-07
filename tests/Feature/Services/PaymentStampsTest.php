<?php

/**
 * Payment Stamps Tests
 *
 * Tests stamp awarding when a user makes a payment based on their plan:
 *
 * Formula: stamps = floor(bill_amount / stamp_denomination) * stamps_per_denomination
 *
 * Example with plan { stamp_denomination: 100, stamps_per_denomination: 2 }:
 *   - Bill ₹250 → floor(250/100)=2 × 2 = 4 stamps
 *   - Bill ₹99  → floor(99/100)=0 × 2 = 0 stamps
 *   - Bill ₹500 → floor(500/100)=5 × 2 = 10 stamps
 *
 * Flow tested:
 *   User pays bill → Transaction created → StampService::awardStampsForBill()
 *     → Gets user's plan via effectiveSubscription()
 *     → Calculates stamps using plan.calculateStampsForAmount(bill)
 *     → Creates Stamp records with source=BillPayment
 *     → Increments campaign.issued_stamps_cache
 *     → Dispatches StampsIssued event
 *
 * Also tested: stamps for coupon redemptions (same formula, different source).
 */

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Enums\StampSource;
use App\Enums\StampStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionType;
use App\Models\Campaign;
use App\Models\Stamp;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\StampService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(StampService::class);

    // Create a standard plan: every ₹100 in a bill → 2 stamps
    $this->plan = SubscriptionPlan::factory()->create([
        'name' => 'Gold Plan',
        'is_default' => false,
        'price' => 999,
        'stamps_on_purchase' => 5,
        'stamp_denomination' => 100,    // ₹100 per unit
        'stamps_per_denomination' => 2,  // 2 stamps per ₹100
        'max_discounted_bills' => 20,
        'duration_days' => 30,
    ]);

    // Create campaign and link to plan
    $this->campaign = Campaign::factory()->create([
        'code' => 'GOLDCAMP',
        'stamp_slots' => 6,
        'stamp_slot_min' => 1,
        'stamp_slot_max' => 49,
        'is_active' => true,
        'status' => CampaignStatus::Active,
        'stamp_target' => 10000,
        'issued_stamps_cache' => 0,
    ]);
    $this->plan->campaigns()->attach($this->campaign->id);

    // Create user with active subscription + primary campaign
    $this->user = User::factory()->create(['primary_campaign_id' => $this->campaign->id]);
    $this->user->campaigns()->attach($this->campaign->id, [
        'is_primary' => true,
        'subscribed_at' => now(),
    ]);
    UserSubscription::create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(30),
    ]);
});

// ────────────────────────────────────────────────────────────────────────────────
// calculateStampsForAmount (model-level formula)
// ────────────────────────────────────────────────────────────────────────────────

test('calculateStampsForAmount gives correct stamps for exact denomination', function () {
    // ₹500 bill → floor(500/100)=5 → 5×2 = 10 stamps
    expect($this->plan->calculateStampsForAmount(500))->toBe(10);
});

test('calculateStampsForAmount truncates partial denominations', function () {
    // ₹250 → floor(250/100)=2 → 2×2 = 4 stamps (not 5, because ₹50 < ₹100)
    expect($this->plan->calculateStampsForAmount(250))->toBe(4);
});

test('calculateStampsForAmount returns zero for bills below one denomination', function () {
    // ₹99 → floor(99/100)=0 → 0×2 = 0 stamps
    expect($this->plan->calculateStampsForAmount(99))->toBe(0);
});

test('calculateStampsForAmount returns zero for zero bill', function () {
    expect($this->plan->calculateStampsForAmount(0))->toBe(0);
});

test('calculateStampsForAmount handles large bills', function () {
    // ₹10,000 → floor(10000/100)=100 → 100×2 = 200 stamps
    expect($this->plan->calculateStampsForAmount(10000))->toBe(200);
});

test('calculateStampsForAmount with different denomination settings', function () {
    // Plan: every ₹50 → 1 stamp
    $plan = SubscriptionPlan::factory()->create([
        'stamp_denomination' => 50,
        'stamps_per_denomination' => 1,
    ]);

    // ₹175 → floor(175/50)=3 → 3×1 = 3 stamps
    expect($plan->calculateStampsForAmount(175))->toBe(3);
});

// ────────────────────────────────────────────────────────────────────────────────
// awardStampsForBill (service-level stamp creation)
// ────────────────────────────────────────────────────────────────────────────────

test('awardStampsForBill creates correct number of stamps for bill payment', function () {
    // ₹300 bill → floor(300/100)=3 → 3×2 = 6 stamps
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 300,
        'amount' => 280,
        'payment_status' => PaymentStatus::Paid,
        'type' => TransactionType::CouponRedemption,
    ]);

    $stampCount = $this->service->awardStampsForBill($transaction);

    expect($stampCount)->toBe(6);

    $stamps = Stamp::where('user_id', $this->user->id)
        ->where('source', StampSource::BillPayment)
        ->get();
    expect($stamps)->toHaveCount(6);
});

test('awardStampsForBill stamps have correct attributes', function () {
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 200,
        'amount' => 190,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $this->service->awardStampsForBill($transaction);

    $stamps = Stamp::where('user_id', $this->user->id)->get();
    foreach ($stamps as $stamp) {
        expect($stamp->source)->toBe(StampSource::BillPayment)
            ->and($stamp->status)->toBe(StampStatus::Used)
            ->and($stamp->campaign_id)->toBe($this->campaign->id)
            ->and($stamp->transaction_id)->toBe($transaction->id)
            ->and($stamp->code)->toStartWith('GOLDCAMP-');
    }
});

test('awardStampsForBill uses original_bill_amount for calculation', function () {
    // original_bill_amount=500, but discounted amount=200
    // Formula should use original_bill_amount → floor(500/100)=5 → 5×2=10 stamps
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 500,
        'amount' => 200, // discounted amount
        'payment_status' => PaymentStatus::Paid,
    ]);

    $stampCount = $this->service->awardStampsForBill($transaction);

    expect($stampCount)->toBe(10);
});

test('awardStampsForBill returns zero for small bills', function () {
    // ₹50 bill → floor(50/100)=0 → 0 stamps
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 50,
        'amount' => 40,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $stampCount = $this->service->awardStampsForBill($transaction);

    expect($stampCount)->toBe(0);
    expect(Stamp::where('user_id', $this->user->id)->where('source', StampSource::BillPayment)->count())->toBe(0);
});

test('awardStampsForBill increments campaign issued_stamps_cache', function () {
    $initialCache = $this->campaign->issued_stamps_cache;

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 400,
        'amount' => 380,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $stampCount = $this->service->awardStampsForBill($transaction);

    // ₹400 → floor(400/100)=4 → 4×2=8 stamps
    expect($stampCount)->toBe(8);
    // Cache is incremented by createStamps() and may also be incremented by
    // the StampsIssued event listener (BountyService::onStampsIssued)
    expect($this->campaign->fresh()->issued_stamps_cache)->toBeGreaterThanOrEqual($initialCache + 8);
});

test('awardStampsForBill resolves user primary campaign automatically', function () {
    // Don't pass a campaignId — service should resolve from user's primary_campaign_id
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 200,
        'amount' => 190,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $stampCount = $this->service->awardStampsForBill($transaction);

    expect($stampCount)->toBe(4); // floor(200/100)=2 × 2 = 4
    $stamps = Stamp::where('user_id', $this->user->id)->get();
    foreach ($stamps as $stamp) {
        expect($stamp->campaign_id)->toBe($this->campaign->id);
    }
});

test('awardStampsForBill returns zero when user has no campaign', function () {
    // User without any campaign subscription
    $user = User::factory()->create(['primary_campaign_id' => null]);
    UserSubscription::create([
        'user_id' => $user->id,
        'plan_id' => $this->plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(30),
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'original_bill_amount' => 500,
        'amount' => 480,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $stampCount = $this->service->awardStampsForBill($transaction);

    expect($stampCount)->toBe(0);
});

test('awardStampsForBill uses specific campaign when provided', function () {
    $otherCampaign = Campaign::factory()->create([
        'code' => 'OTHER',
        'is_active' => true,
        'status' => CampaignStatus::Active,
        'stamp_target' => 1000,
    ]);
    // Subscribe user to this campaign too
    $this->user->campaigns()->attach($otherCampaign->id, [
        'is_primary' => false,
        'subscribed_at' => now(),
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 200,
        'amount' => 190,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $stampCount = $this->service->awardStampsForBill($transaction, $otherCampaign->id);

    expect($stampCount)->toBe(4); // floor(200/100)=2 × 2 = 4
    $stamps = Stamp::where('user_id', $this->user->id)->get();
    foreach ($stamps as $stamp) {
        expect($stamp->campaign_id)->toBe($otherCampaign->id);
    }
});

// ────────────────────────────────────────────────────────────────────────────────
// awardStampsForCouponRedemption (same formula, different source)
// ────────────────────────────────────────────────────────────────────────────────

test('awardStampsForCouponRedemption uses same formula as bill payment', function () {
    // ₹300 bill via coupon → floor(300/100)=3 → 3×2 = 6 stamps
    $transaction = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 300,
        'amount' => 270,
        'payment_status' => PaymentStatus::Paid,
        'type' => TransactionType::CouponRedemption,
    ]);

    $stampCount = $this->service->awardStampsForCouponRedemption($transaction);

    expect($stampCount)->toBe(6);
    $stamps = Stamp::where('user_id', $this->user->id)->get();
    foreach ($stamps as $stamp) {
        expect($stamp->source)->toBe(StampSource::CouponRedemption);
    }
});

// ────────────────────────────────────────────────────────────────────────────────
// Plan purchase stamps (upgradePlan flow)
// ────────────────────────────────────────────────────────────────────────────────

test('awardStampsForPlanPurchase awards exact stamps_on_purchase count', function () {
    // Plan has stamps_on_purchase = 5
    $stampCount = $this->service->awardStampsForPlanPurchase($this->user, $this->plan);

    expect($stampCount)->toBe(5);

    $stamps = Stamp::where('user_id', $this->user->id)
        ->where('source', StampSource::PlanPurchase)
        ->get();
    expect($stamps)->toHaveCount(5);
});

test('awardStampsForPlanPurchase stamps are linked to correct campaign', function () {
    $this->service->awardStampsForPlanPurchase($this->user, $this->plan);

    $stamps = Stamp::where('user_id', $this->user->id)->get();
    foreach ($stamps as $stamp) {
        expect($stamp->campaign_id)->toBe($this->campaign->id)
            ->and($stamp->source)->toBe(StampSource::PlanPurchase);
    }
});

test('no stamps awarded when plan has zero stamps_on_purchase', function () {
    $freePlan = SubscriptionPlan::factory()->create([
        'stamps_on_purchase' => 0,
    ]);

    $stampCount = $this->service->awardStampsForPlanPurchase($this->user, $freePlan);

    expect($stampCount)->toBe(0);
    expect(Stamp::where('user_id', $this->user->id)->count())->toBe(0);
});

// ────────────────────────────────────────────────────────────────────────────────
// Multiple bills accumulate stamps
// ────────────────────────────────────────────────────────────────────────────────

test('multiple bill payments accumulate stamps for the user', function () {
    // Bill 1: ₹200 → 4 stamps
    $txn1 = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 200,
        'amount' => 190,
        'payment_status' => PaymentStatus::Paid,
    ]);
    $this->service->awardStampsForBill($txn1);

    // Bill 2: ₹350 → floor(350/100)=3 → 3×2=6 stamps
    $txn2 = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 350,
        'amount' => 330,
        'payment_status' => PaymentStatus::Paid,
    ]);
    $this->service->awardStampsForBill($txn2);

    // Total: 4 + 6 = 10 stamps
    $totalStamps = Stamp::where('user_id', $this->user->id)->count();
    expect($totalStamps)->toBe(10);
});

// ────────────────────────────────────────────────────────────────────────────────
// Different plans give different stamp rates
// ────────────────────────────────────────────────────────────────────────────────

test('different plans yield different stamps for same bill amount', function () {
    // Silver plan: every ₹200 → 1 stamp
    $silverPlan = SubscriptionPlan::factory()->create([
        'stamp_denomination' => 200,
        'stamps_per_denomination' => 1,
        'duration_days' => 30,
    ]);

    // Create silver user
    $silverUser = User::factory()->create(['primary_campaign_id' => $this->campaign->id]);
    $silverUser->campaigns()->attach($this->campaign->id, [
        'is_primary' => true,
        'subscribed_at' => now(),
    ]);
    UserSubscription::create([
        'user_id' => $silverUser->id,
        'plan_id' => $silverPlan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(30),
    ]);

    // Same ₹500 bill for both users
    // Gold user: floor(500/100)×2 = 10 stamps
    $goldTxn = Transaction::factory()->create([
        'user_id' => $this->user->id,
        'original_bill_amount' => 500,
        'amount' => 480,
        'payment_status' => PaymentStatus::Paid,
    ]);
    $goldStamps = $this->service->awardStampsForBill($goldTxn);

    // Silver user: floor(500/200)×1 = 2 stamps
    $silverTxn = Transaction::factory()->create([
        'user_id' => $silverUser->id,
        'original_bill_amount' => 500,
        'amount' => 480,
        'payment_status' => PaymentStatus::Paid,
    ]);
    $silverStamps = $this->service->awardStampsForBill($silverTxn);

    expect($goldStamps)->toBe(10);
    expect($silverStamps)->toBe(2);
});

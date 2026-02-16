<?php

namespace App\Http\Controllers;

use App\Models\DiscountCoupon;
use App\Models\MerchantLocation;
use App\Models\Transaction;
use App\Services\CouponRedemptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CouponController extends Controller
{
    public function __construct(protected CouponRedemptionService $redemptionService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $planId = $user?->activeSubscription?->plan_id;

        $coupons = DiscountCoupon::query()
            ->when($planId, fn ($q) => $q->forPlan($planId))
            ->active()
            ->with(['merchantLocation.merchant', 'category'])
            ->latest()
            ->paginate(9);

        // Pass available locations for redemption dropdown
        $locations = MerchantLocation::with('merchant')->get()->map(function ($loc) {
            return [
                'id' => $loc->id,
                'name' => $loc->branch_name.' ('.$loc->merchant->name.')',
            ];
        });

        return Inertia::render('Coupons/Index', [
            'coupons' => $coupons,
            'locations' => $locations,
            'planName' => $user?->activeSubscription?->plan->name ?? 'Free Tier',
            'isLoggedIn' => (bool) $user,
        ]);
    }

    public function redeem(Request $request, DiscountCoupon $coupon)
    {
        $validated = $request->validate([
            'merchant_location_id' => 'required|exists:merchant_locations,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'You must be logged in to redeem a coupon.');
        }

        $merchantLocation = MerchantLocation::findOrFail($validated['merchant_location_id']);
        $amount = (float) $validated['amount'];

        try {
            DB::transaction(function () use ($user, $coupon, $merchantLocation, $amount) {
                // 1. Create Transaction (Simulated for this demo)
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'merchant_location_id' => $merchantLocation->id,
                    'amount' => $amount,
                    'commission_amount' => $amount * ($merchantLocation->commission_percentage / 100),
                ]);

                // 2. Redeem Coupon
                $this->redemptionService->redeemCoupon($user, $coupon, $transaction);

                // Fire events
                \App\Events\CommissionEarned::dispatch($transaction);
            });

            return redirect()->back()->with('success', 'Coupon redeemed successfully!');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['amount' => $e->getMessage()]);
        }
    }
}

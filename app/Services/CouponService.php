<?php

namespace App\Services;

use App\Models\DiscountCoupon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CouponService
{
    /**
     * Generate bulk unique coupon codes.
     *
     * @param  array{coupon_category_id?: int, merchant_location_id?: int|null, title: string, description?: string|null, discount_type: string, discount_value: float, min_order_value?: float|null, max_discount_amount?: float|null, usage_limit?: int|null, usage_per_user?: int|null, starts_at?: string|null, expires_at?: string|null, is_active?: bool}  $attributes
     * @return Collection<int, DiscountCoupon>
     */
    public function generateBulk(int $count, array $attributes, string $prefix = 'KUT-'): Collection
    {
        if ($count < 1 || $count > 10000) {
            throw new \InvalidArgumentException('Coupon count must be between 1 and 10,000.');
        }

        return DB::transaction(function () use ($count, $attributes, $prefix) {
            $coupons = collect();
            $existingCodes = DiscountCoupon::where('code', 'LIKE', $prefix.'%')
                ->pluck('code')
                ->flip();

            $chunks = collect();

            for ($i = 0; $i < $count; $i++) {
                $code = $this->generateUniqueCode($prefix, $existingCodes);
                $existingCodes[$code] = true;

                $chunks->push(array_merge($attributes, [
                    'code' => $code,
                    'is_active' => $attributes['is_active'] ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            // Insert in chunks of 500 for performance
            foreach ($chunks->chunk(500) as $chunk) {
                DiscountCoupon::insert($chunk->toArray());
            }

            // Fetch the created coupons
            $codes = $chunks->pluck('code')->toArray();
            $coupons = DiscountCoupon::whereIn('code', $codes)->get();

            return $coupons;
        });
    }

    /**
     * Generate a unique coupon code that doesn't exist in the given set.
     *
     * @param  \Illuminate\Support\Collection<string, mixed>|array<string, mixed>  $existingCodes
     */
    private function generateUniqueCode(string $prefix, $existingCodes): string
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $code = $prefix.strtoupper(Str::random(8));
            $attempt++;

            if ($attempt >= $maxAttempts) {
                throw new \RuntimeException('Unable to generate a unique coupon code after '.$maxAttempts.' attempts.');
            }
        } while (isset($existingCodes[$code]));

        return $code;
    }
}

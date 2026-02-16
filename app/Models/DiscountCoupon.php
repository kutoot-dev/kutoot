<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DiscountCoupon extends Model
{
    /** @use HasFactory<\Database\Factories\DiscountCouponFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'min_order_value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CouponCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CouponCategory::class, 'coupon_category_id');
    }

    /**
     * @return BelongsTo<MerchantLocation, $this>
     */
    public function merchantLocation(): BelongsTo
    {
        return $this->belongsTo(MerchantLocation::class);
    }

    /**
     * @return BelongsToMany<SubscriptionPlan, $this>
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'plan_coupon_access', 'coupon_id', 'plan_id');
    }

    /**
     * @return HasMany<CouponRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'coupon_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeForPlan(Builder $query, int $planId): Builder
    {
        return $query->whereHas('plans', fn (Builder $q) => $q->where('plan_id', $planId));
    }

    public function getSourceAttribute(): string
    {
        return $this->merchant_location_id ? 'merchant' : 'kutoot';
    }
}

<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
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

    protected $fillable = [
        'coupon_category_id',
        'merchant_location_id',
        'title',
        'description',
        'discount_type',
        'discount_value',
        'min_order_value',
        'max_discount_amount',
        'code',
        'usage_limit',
        'usage_per_user',
        'starts_at',
        'expires_at',
        'is_active',
        'approval_status',
        'rejection_reason',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "Coupon \"{$this->title}\" was {$eventName}");
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
            'approval_status' => ApprovalStatus::class,
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
     *
     * Eligibility is determined through the coupon's category.
     * A coupon is eligible for a plan if its category is linked to that plan.
     */
    public function eligiblePlans(): BelongsToMany
    {
        return $this->category?->subscriptionPlans() ?? $this->belongsToMany(SubscriptionPlan::class)->whereRaw('1 = 0');
    }

    /**
     * Check if this coupon is eligible for a given plan.
     */
    public function isEligibleForPlan(int $planId): bool
    {
        return $this->category?->subscriptionPlans()->where('plan_id', $planId)->exists() ?? false;
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
            ->where('approval_status', ApprovalStatus::Approved)
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', ApprovalStatus::Approved);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', ApprovalStatus::Pending);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('approval_status', ApprovalStatus::Rejected);
    }

    public function scopeForPlan(Builder $query, int $planId): Builder
    {
        return $query->whereHas('category.subscriptionPlans', fn (Builder $q) => $q->where('subscription_plans.id', $planId));
    }

    public function getSourceAttribute(): string
    {
        return $this->merchant_location_id ? 'merchant' : 'kutoot';
    }

    /**
     * Legacy accessor for `status` used in the merchant panel until
     * approval_status was introduced. Keeping this ensures `$model->status`
     * returns the same string as approval_status and avoids errors when the
     * attribute is referenced elsewhere in the codebase.
     */
    public function getStatusAttribute(): string
    {
        return $this->approval_status?->value ?? '';
    }
}

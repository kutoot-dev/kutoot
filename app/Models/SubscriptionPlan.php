<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionPlanFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'sort_order',
        'best_value',
        'price',
        'original_price',
        'is_default',
        'stamps_on_purchase',
        'stamp_denomination',
        'stamps_per_denomination',
        'max_discounted_bills',
        'max_redeemable_amount',
        'duration_days',
        'terms_and_conditions',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "Subscription plan \"{$this->name}\" was {$eventName}");
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'stamp_denomination' => 'decimal:2',
            'max_redeemable_amount' => 'decimal:2',
            'is_default' => 'boolean',
            'best_value' => 'boolean',
        ];
    }

    /**
     * Scope: order plans by their display sort_order.
     */
    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Calculate stamps earned for a given bill amount.
     *
     * Formula: floor(amount / denomination) * stamps_per_denomination
     */
    public function calculateStampsForAmount(float $amount): int
    {
        $denomination = (float) $this->stamp_denomination;

        if ($denomination <= 0) {
            return 0;
        }

        return (int) floor($amount / $denomination) * $this->stamps_per_denomination;
    }

    /**
     * @return HasMany<UserSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }

    /**
     * @return HasMany<SubscriptionConsent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(SubscriptionConsent::class, 'plan_id');
    }

    /**
     * Ensure only one plan is marked as best value at a time.
     */
    protected static function booted(): void
    {
        static::saving(function (SubscriptionPlan $plan) {
            if ($plan->best_value) {
                // clear flag on other plans before saving this one
                static::where('id', '!=', $plan->id)
                    ->where('best_value', true)
                    ->update(['best_value' => false]);
            }
        });
    }

    /**
     * @return BelongsToMany<Campaign, $this>
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'plan_campaign_access', 'plan_id', 'campaign_id');
    }

    /**
     * @return BelongsToMany<CouponCategory, $this>
     */
    public function couponCategories(): BelongsToMany
    {
        return $this->belongsToMany(CouponCategory::class, 'plan_coupon_category_access', 'plan_id', 'coupon_category_id');
    }
}

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
        'price',
        'is_default',
        'stamps_on_purchase',
        'stamps_per_100',
        'max_discounted_bills',
        'max_redeemable_amount',
        'duration_days',
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
            'max_redeemable_amount' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return HasMany<UserSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
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

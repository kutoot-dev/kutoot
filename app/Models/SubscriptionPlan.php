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
     * @return BelongsToMany<DiscountCoupon, $this>
     */
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(DiscountCoupon::class, 'plan_coupon_access', 'plan_id', 'coupon_id');
    }
}

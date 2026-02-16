<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\CreatorType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Campaign extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'category_id',
        'creator_id',
        'creator_type',
        'reward_name',
        'status',
        'start_date',
        'reward_cost_target',
        'stamp_target',
        'collected_commission_cache',
        'issued_stamps_cache',
        'winner_announcement_date',
        'is_active',
    ];

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
            'status' => CampaignStatus::class,
            'creator_type' => CreatorType::class,
            'start_date' => 'date',
            'reward_cost_target' => 'decimal:2',
            'collected_commission_cache' => 'decimal:2',
            'winner_announcement_date' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CampaignCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CampaignCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return BelongsToMany<SubscriptionPlan, $this>
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'plan_campaign_access', 'campaign_id', 'plan_id');
    }

    /**
     * @return HasMany<Stamp, $this>
     */
    public function stamps(): HasMany
    {
        return $this->hasMany(Stamp::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::Active);
    }

    public function scopeForPlan(Builder $query, int $planId): Builder
    {
        return $query->whereHas('plans', fn (Builder $q) => $q->where('plan_id', $planId));
    }
}

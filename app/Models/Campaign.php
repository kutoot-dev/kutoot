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
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Campaign extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'code',
        'category_id',
        'creator_id',
        'creator_type',
        'reward_name',
        'description',
        'status',
        'start_date',
        'reward_cost_target',
        'stamp_target',
        'stamp_slots',
        'stamp_slot_min',
        'stamp_slot_max',
        'stamp_editable_on_plan_purchase',
        'stamp_editable_on_coupon_redemption',
        'collected_commission_cache',
        'issued_stamps_cache',
        'marketing_bounty_percentage',
        'winner_announcement_date',
        'is_active',
        'is_premium',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "Campaign \"{$this->reward_name}\" was {$eventName}");
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
            'marketing_bounty_percentage' => 'integer',
            'winner_announcement_date' => 'datetime',
            'stamp_slots' => 'integer',
            'stamp_slot_min' => 'integer',
            'stamp_slot_max' => 'integer',
            'stamp_editable_on_plan_purchase' => 'boolean',
            'stamp_editable_on_coupon_redemption' => 'boolean',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
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

    /**
     * Users who have subscribed to this campaign.
     *
     * @return BelongsToMany<User, $this>
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'campaign_user')
            ->withPivot(['is_primary', 'subscribed_at'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::Active);
    }

    public function scopeForPlan(Builder $query, int $planId): Builder
    {
        return $query->whereHas('plans', fn (Builder $q) => $q->where('plan_id', $planId));
    }

    public function scopePremium(Builder $query): Builder
    {
        return $query->where('is_premium', true);
    }

    /**
     * Whether this campaign has a configured stamp code format.
     */
    public function hasStampConfig(): bool
    {
        return $this->code !== null
            && $this->stamp_slots !== null
            && $this->stamp_slot_min !== null
            && $this->stamp_slot_max !== null;
    }

    /**
     * Number of digits needed to represent the max slot value (for zero-padding).
     */
    public function getSlotDigitCount(): int
    {
        return strlen((string) $this->stamp_slot_max);
    }

    /**
     * Calculate total possible stamp combinations: C(range, slots).
     */
    public function getPossibleCombinations(): int
    {
        if (! $this->hasStampConfig()) {
            return 0;
        }

        $range = $this->stamp_slot_max - $this->stamp_slot_min + 1;
        $slots = $this->stamp_slots;

        if ($slots > $range) {
            return 0;
        }

        return $this->binomialCoefficient($range, $slots);
    }

    /**
     * Format slot values into a full stamp code string.
     *
     * @param  array<int>  $slotValues
     */
    public function formatStampCode(array $slotValues): string
    {
        $digits = $this->getSlotDigitCount();
        $paddedSlots = array_map(
            fn (int $value): string => str_pad((string) $value, $digits, '0', STR_PAD_LEFT),
            $slotValues,
        );

        return strtoupper($this->code).'-'.implode('-', $paddedSlots);
    }

    /**
     * Generate a sample stamp code for preview purposes.
     */
    public function generateSampleStampCode(): string
    {
        if (! $this->hasStampConfig()) {
            return 'STP-XXXXXXXX';
        }

        $range = range($this->stamp_slot_min, $this->stamp_slot_max);
        $selected = collect($range)->random($this->stamp_slots)->sort()->values()->all();

        return $this->formatStampCode($selected);
    }

    /**
     * Calculate the binomial coefficient C(n, k).
     */
    protected function binomialCoefficient(int $n, int $k): int
    {
        if ($k > $n) {
            return 0;
        }

        if ($k === 0 || $k === $n) {
            return 1;
        }

        // Optimize by using smaller k
        if ($k > $n - $k) {
            $k = $n - $k;
        }

        $result = 1;
        for ($i = 0; $i < $k; $i++) {
            $result = intdiv($result * ($n - $i), $i + 1);
        }

        return $result;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('media')
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                'video/mp4', 'video/webm', 'video/quicktime',
            ]);

        $this->addMediaCollection('sponsor_image')
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            ])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Conversions for 'media' collection (images and videos)
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 300, 300)
            ->format('webp')
            ->quality(90)
            ->nonOptimized()
            ->nonQueued()
            ->performOnCollections('media');

        $this->addMediaConversion('preview')
            ->fit(Fit::Contain, 1920, 1080)
            ->format('webp')
            ->quality(95)
            ->nonOptimized()
            ->withResponsiveImages()
            ->performOnCollections('media');

        // Conversion for 'sponsor_image' collection
        $this->addMediaConversion('sponsor_thumb')
            ->fit(Fit::Contain, 400, 224)
            ->format('webp')
            ->quality(90)
            ->nonOptimized()
            ->nonQueued()
            ->performOnCollections('sponsor_image');
    }
}

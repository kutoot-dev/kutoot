<?php

namespace App\Models;

use App\Enums\LoanStatus;
use App\Enums\TargetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MerchantLocation extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\MerchantLocationFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'merchant_id',
        'merchant_category_id',
        'state_id',
        'city_id',
        'branch_name',
        'commission_percentage',
        'star_rating',
        'is_active',
        'monthly_target_type',
        'monthly_target_value',
        'deduct_commission_from_target',
        'latitude',
        'longitude',
        'address',
        'gst_number',
        'pan_number',
        'bank_name',
        'sub_bank_name',
        'account_number',
        'ifsc_code',
        'upi_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission_percentage' => 'decimal:2',
            'star_rating' => 'decimal:1',
            'is_active' => 'boolean',
            'monthly_target_type' => TargetType::class,
            'monthly_target_value' => 'decimal:2',
            'deduct_commission_from_target' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "Store location \"{$this->branch_name}\" was {$eventName}");
    }

    /**
     * @return BelongsTo<Merchant, $this>
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return BelongsTo<MerchantCategory, $this>
     */
    public function merchantCategory(): BelongsTo
    {
        return $this->belongsTo(MerchantCategory::class);
    }

    /**
     * @return BelongsTo<\Nnjeim\World\Models\State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(\Nnjeim\World\Models\State::class);
    }

    /**
     * @return BelongsTo<\Nnjeim\World\Models\City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(\Nnjeim\World\Models\City::class);
    }

    /**
     * @return BelongsToMany<\App\Models\User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'merchant_location_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    /**
     * @return HasOne<QrCode, $this>
     */
    public function primaryQrCode(): HasOne
    {
        return $this->hasOne(QrCode::class)->where('is_primary', true);
    }

    /**
     * @return HasMany<DiscountCoupon, $this>
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(DiscountCoupon::class);
    }

    /**
     * @return HasMany<MerchantLocationMonthlySummary, $this>
     */
    public function monthlySummaries(): HasMany
    {
        return $this->hasMany(MerchantLocationMonthlySummary::class);
    }

    /**
     * @return HasMany<MerchantLocationLoan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(MerchantLocationLoan::class);
    }

    /**
     * @return HasOne<MerchantNotificationSetting, $this>
     */
    public function notificationSetting(): HasOne
    {
        return $this->hasOne(MerchantNotificationSetting::class);
    }

    /**
     * @return HasOne<MerchantLocationLoan, $this>
     */
    public function activeLoan(): HasOne
    {
        return $this->hasOne(MerchantLocationLoan::class)
            ->where('status', LoanStatus::Active)
            ->latest('approved_at');
    }

    /**
     * Check if this location participates in the streak/loan program.
     */
    public function hasMonthlyTarget(): bool
    {
        return $this->monthly_target_type !== null && $this->monthly_target_value !== null && (float) $this->monthly_target_value > 0;
    }

    /**
     * Calculate the current consecutive streak of months meeting the target.
     * Counts backward from the previous calendar month.
     */
    public function getCurrentStreak(): int
    {
        if (! $this->hasMonthlyTarget()) {
            return 0;
        }

        $summaries = $this->monthlySummaries()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get(['year', 'month', 'target_met']);

        $streak = 0;
        $expectedYear = (int) now()->format('Y');
        $expectedMonth = (int) now()->format('m') - 1;

        if ($expectedMonth === 0) {
            $expectedMonth = 12;
            $expectedYear--;
        }

        foreach ($summaries as $summary) {
            if ((int) $summary->year === $expectedYear && (int) $summary->month === $expectedMonth && $summary->target_met) {
                $streak++;
                $expectedMonth--;
                if ($expectedMonth === 0) {
                    $expectedMonth = 12;
                    $expectedYear--;
                }
            } else {
                break;
            }
        }

        return $streak;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('media')
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                'video/mp4', 'video/webm', 'video/quicktime',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 300, 300)
            ->format('webp')
            ->quality(90)
            ->nonOptimized()
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->fit(Fit::Contain, 1920, 1080)
            ->format('webp')
            ->quality(95)
            ->nonOptimized()
            ->withResponsiveImages();
    }
}

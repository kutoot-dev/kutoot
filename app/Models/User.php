<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\SubscriptionStatus;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasEmailAuthentication, HasMedia, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, InteractsWithEmailAuthentication, InteractsWithMedia, LogsActivity, Notifiable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "User \"{$this->name}\" was {$eventName}");
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'primary_campaign_id',
        'otp_code',
        'otp_expires_at',
        'has_email_authentication',
        'gender',
        'country_id',
        'state_id',
        'city_id',
        'pin_code',
        'full_address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'otp_expires_at',
    ];

    /**
     * Attributes that should be appended to the array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_picture_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'primary_campaign_id' => 'integer',
            'otp_expires_at' => 'datetime',
            'has_email_authentication' => 'boolean',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Campaign, $this>
     */
    public function primaryCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'primary_campaign_id');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Campaigns the user has subscribed to.
     *
     * @return BelongsToMany<Campaign, $this>
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_user')
            ->withPivot(['is_primary', 'subscribed_at'])
            ->withTimestamps();
    }

    /**
     * Get only the user's subscribed campaigns that are accessible under their current plan.
     *
     * @return BelongsToMany<Campaign, $this>
     */
    public function activeCampaigns(): BelongsToMany
    {
        return $this->campaigns()
            ->where('is_active', true)
            ->where('status', \App\Enums\CampaignStatus::Active);
    }

    /**
     * Check whether the user is subscribed to a given campaign.
     */
    public function isSubscribedToCampaign(int $campaignId): bool
    {
        return $this->campaigns()->where('campaigns.id', $campaignId)->exists();
    }

    /**
     * Get the IDs of campaigns accessible under the user's current plan.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function accessibleCampaignIds(): \Illuminate\Support\Collection
    {
        $subscription = $this->effectiveSubscription();

        if (! $subscription) {
            return collect();
        }

        $plan = SubscriptionPlan::find($subscription->plan_id);

        return $plan ? $plan->campaigns()->pluck('campaigns.id') : collect();
    }

    /**
     * @return HasMany<UserSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * @return HasOne<UserSubscription, $this>
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)->where('status', SubscriptionStatus::Active)->latestOfMany();
    }

    /**
     * Get the effective subscription, falling back to the Base Plan if no active subscription exists.
     */
    public function effectiveSubscription(): ?UserSubscription
    {
        $active = $this->activeSubscription;

        if ($active) {
            return $active;
        }

        // Fallback to Base Plan (is_default)
        $basePlan = SubscriptionPlan::where('is_default', true)->first();

        if ($basePlan) {
            return new UserSubscription([
                'user_id' => $this->id,
                'plan_id' => $basePlan->id,
                'status' => SubscriptionStatus::Active,
            ]);
        }

        return null;
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(\Spatie\Image\Enums\Fit::Contain, 300, 300)
            ->format('webp')
            ->quality(80)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->fit(\Spatie\Image\Enums\Fit::Contain, 800, 600)
            ->format('webp')
            ->quality(85)
            ->withResponsiveImages();
    }

    /**
     * Get the profile picture URL (avatar) if present.
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('avatar');

        return $media ? $media->getUrl() : null;
    }

    /**
     * @return HasMany<Stamp, $this>
     */
    public function stamps(): HasMany
    {
        return $this->hasMany(Stamp::class);
    }

    /**
     * @return HasMany<CouponRedemption, $this>
     */
    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * @return BelongsToMany<MerchantLocation, $this>
     */
    public function merchantLocations(): BelongsToMany
    {
        return $this->belongsToMany(MerchantLocation::class, 'merchant_location_user');
    }

    /**
     * Get the merchant associated with this user (via their first merchant location).
     */
    public function getMerchantAttribute(): ?Merchant
    {
        $location = $this->merchantLocations->first();

        return $location?->merchant;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // if ($panel->getId() === 'admin') {
        //     return $this->hasRole(['Super Admin', 'Merchant Admin']);
        // }

        return true;
    }

    /**
     * @return Collection<int, MerchantLocation>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->merchantLocations;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->merchantLocations->contains($tenant);
    }
}

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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasEmailAuthentication, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, InteractsWithEmailAuthentication, LogsActivity, Notifiable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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
    public function primaryCampaign(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'primary_campaign_id');
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

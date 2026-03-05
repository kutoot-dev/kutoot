<?php

namespace App\Models;

use App\Enums\QrCodeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_code',
        'token',
        'merchant_location_id',
        'status',
        'is_primary',
        'linked_at',
        'linked_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'linked_at' => 'datetime',
            'status' => QrCodeStatus::class,
            'is_primary' => 'boolean',
        ];
    }

    public function merchantLocation(): BelongsTo
    {
        return $this->belongsTo(MerchantLocation::class);
    }

    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeLinked($query)
    {
        return $query->where('status', QrCodeStatus::Linked);
    }

    public function getUrlAttribute(): string
    {
        return route('qr.scan', ['token' => $this->token]);
    }

    public function getShortUrlAttribute(): string
    {
        $frontendUrl = config('app.frontend_url', 'https://www.kutoot.com');
        return rtrim($frontendUrl, '/') . '/q/' . $this->token;
    }

    /**
     * Make this QR code the primary for its merchant location.
     * Only linked QR codes with a merchant_location can be primary.
     */
    public function makePrimary(): bool
    {
        if ($this->status !== QrCodeStatus::Linked || ! $this->merchant_location_id) {
            return false;
        }

        return DB::transaction(function () {
            // Unset all other primaries for this location
            static::where('merchant_location_id', $this->merchant_location_id)
                ->where('id', '!=', $this->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $this->update(['is_primary' => true]);

            return true;
        });
    }

    /**
     * Remove primary status and optionally auto-promote the next oldest linked QR.
     */
    public function removePrimary(bool $autoPromote = true): void
    {
        if (! $this->is_primary) {
            return;
        }

        $this->is_primary = false;

        if ($autoPromote && $this->merchant_location_id) {
            $next = static::where('merchant_location_id', $this->merchant_location_id)
                ->where('id', '!=', $this->id)
                ->where('status', QrCodeStatus::Linked)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }
    }
}

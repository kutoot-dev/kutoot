<?php

namespace App\Models;

use App\Enums\StampSource;
use App\Enums\StampStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Stamp extends Model
{
    /** @use HasFactory<\Database\Factories\StampFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'transaction_id',
        'code',
        'source',
        'status',
        'reserved_at',
        'expires_at',
        'editable_until',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'source', 'status', 'campaign_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "Stamp ({$this->code}) was {$eventName}");
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => StampSource::class,
            'status' => StampStatus::class,
            'editable_until' => 'datetime',
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // ── State checks ────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return $this->editable_until !== null && $this->editable_until->isFuture();
    }

    public function isReserved(): bool
    {
        return $this->status === StampStatus::Reserved
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === StampStatus::Reserved
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    public function remainingSeconds(): int
    {
        if (! $this->isReserved()) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }

    // ── Query scopes ────────────────────────────────────────────────

    /**
     * @param  Builder<Stamp>  $query
     * @return Builder<Stamp>
     */
    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('status', StampStatus::Reserved);
    }

    /**
     * @param  Builder<Stamp>  $query
     * @return Builder<Stamp>
     */
    public function scopeUsed(Builder $query): Builder
    {
        return $query->where('status', StampStatus::Used);
    }

    /**
     * Active reservation: reserved AND not yet expired.
     *
     * @param  Builder<Stamp>  $query
     * @return Builder<Stamp>
     */
    public function scopeActiveReservation(Builder $query): Builder
    {
        return $query->where('status', StampStatus::Reserved)
            ->where('expires_at', '>', now());
    }

    /**
     * Expired reservations: reserved but past their expiry window.
     *
     * @param  Builder<Stamp>  $query
     * @return Builder<Stamp>
     */
    public function scopeExpiredReservation(Builder $query): Builder
    {
        return $query->where('status', StampStatus::Reserved)
            ->where('expires_at', '<=', now());
    }

    // ── Relationships ───────────────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}

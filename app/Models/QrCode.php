<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCode extends Model
{
    protected $fillable = [
        'unique_code',
        'token',
        'merchant_location_id',
        'status',
        'linked_at',
        'linked_by',
    ];

    protected $casts = [
        'linked_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function merchantLocation(): BelongsTo
    {
        return $this->belongsTo(MerchantLocation::class);
    }

    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class , 'linked_by');
    }

    public function getUrlAttribute(): string
    {
        return route('qr.scan', ['token' => $this->token]);
    }
}

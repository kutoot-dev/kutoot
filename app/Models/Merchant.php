<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Merchant extends Model
{
    /** @use HasFactory<\Database\Factories\MerchantFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return HasMany<MerchantLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(MerchantLocation::class);
    }
}

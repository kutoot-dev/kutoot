<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantCategory extends Model
{
    /** @use HasFactory<\Database\Factories\MerchantCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'icon',
        'serial',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'serial' => 'integer',
        ];
    }

    /**
     * @return HasMany<MerchantLocation, $this>
     */
    public function merchantLocations(): HasMany
    {
        return $this->hasMany(MerchantLocation::class);
    }
}

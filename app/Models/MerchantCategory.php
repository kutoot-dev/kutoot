<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MerchantCategory extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\MerchantCategoryFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);

        $this->addMediaCollection('icon')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
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

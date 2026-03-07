<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Sponsor extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\SponsorFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'type',
        'link',
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
     * Campaigns this sponsor is associated with.
     *
     * @return BelongsToMany<Campaign, $this>
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_sponsor')
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);

        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
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

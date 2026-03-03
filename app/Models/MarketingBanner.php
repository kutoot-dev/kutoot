<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MarketingBanner extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\MarketingBannerFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'title',
        'subtitle',
        'link_url',
        'link_text',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "Marketing banner \"{$this->title}\" was {$eventName}");
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
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

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

class HeroSetting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'title',
        'description',
        'is_active',
        'locale',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => "Hero setting was {$eventName}");
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero_media')
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                'video/mp4', 'video/webm', 'video/quicktime',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 800, 450)
            ->format('webp')
            ->quality(85)
            ->nonQueued()
            ->performOnCollections('hero_media');

        $this->addMediaConversion('preview')
            ->fit(Fit::Contain, 1920, 1080)
            ->format('webp')
            ->quality(95)
            ->withResponsiveImages()
            ->performOnCollections('hero_media');

        $this->addMediaConversion('mobile')
            ->fit(Fit::Contain, 828, 466)
            ->format('webp')
            ->quality(90)
            ->nonQueued()
            ->performOnCollections('hero_media');
    }

    /**
     * Scope a query to the given locale.
     */
    public function scopeForLocale($query, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $query->where('locale', $locale);
    }

    /**
     * Get the active hero setting (singleton pattern).
     *
     * @param string|null $locale
     */
    public static function active(?string $locale = null): ?self
    {
        $locale = $locale ?? app()->getLocale();
        return static::where('is_active', true)
            ->where('locale', $locale)
            ->latest()
            ->first();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class Sponsor extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\SponsorFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'type',
        'logo', // Keeping these temporarily for migration
        'banner', // Keeping these temporarily for migration
        'link',
        'serial',
        'is_active',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();

        $this->addMediaCollection('banner')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 300, 300)
            ->format('webp')
            ->quality(80)
            ->nonQueued();
    }

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
}

<?php

namespace App\Traits;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Storage;

trait HasImageUrls
{
    /**
     * Get the image URL for the model.
     * This abstracts away whether the image is stored in a column or via Spatie Media Library.
     * 
     * @return string|null
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this instanceof HasMedia) {
            // Try to get thumb first, fallback to original
            $url = $this->getFirstMediaUrl('image', 'thumb');
            if (!$url) {
                $url = $this->getFirstMediaUrl('image');
            }
            return $url ?: null;
        }

        if (isset($this->attributes['logo'])) {
            return Storage::disk('public')->url($this->attributes['logo']);
        }

        if (isset($this->attributes['image'])) {
            return Storage::disk('public')->url($this->attributes['image']);
        }

        if (isset($this->attributes['banner'])) {
            return Storage::disk('public')->url($this->attributes['banner']);
        }

        return null;
    }
}

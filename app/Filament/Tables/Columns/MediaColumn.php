<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\SpatieLaravelMediaLibraryPlugin\Collections\AllMediaCollections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A specialised version of the stock Filament image column that understands
 * when a media collection contains *only* video files.  In that case we return
 * a sentinel value from `getState()` which can be detected by the column's
 * rendering layer and replaced with a generic placeholder image.
 *
 * The implementation borrows heavily from
 * `SpatieMediaLibraryImageColumn` but adds the additional MIME-type
 * filtering and sentinel logic; the parent class's caching helper is reused
 * to ensure we don't perform expensive media lookups multiple times.
 */
class MediaColumn extends SpatieMediaLibraryImageColumn
{
    /**
     * Constant used as the only element of the state array when there are no
     * images but at least one video attached to the record.  This is not a
     * real UUID and should never collide with a genuine media identifier.
     */
    public const VIDEO_ONLY_SENTINEL = '__video_only__';

    /**
     * URL to use for the video placeholder.  If `null` a simple inline SVG is
     * generated lazily by {@see getVideoPlaceholderUrl()}.
     *
     * @var string|null
     */
    protected ?string $videoPlaceholderUrl = null;

    /**
     * Set a custom placeholder URL for video-only media collections.
     */
    public function videoPlaceholderUrl(string $url): static
    {
        $this->videoPlaceholderUrl = $url;

        return $this;
    }

    /**
     * Retrieve the URL that should be displayed when the state sentinel is
     * returned.  Consumers may override this if they want a different graphic
     * or wish to reference a static asset; otherwise we return an inline SVG
     * so that no external file is required.
     */
    protected function getVideoPlaceholderUrl(): string
    {
        if ($this->videoPlaceholderUrl) {
            return $this->videoPlaceholderUrl;
        }

        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
    <path d="M17 10.5v-1c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v7c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2v-1l4 4v-11l-4 4z"/>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * {@inheritdoc}
     *
     * We replicate the parent implementation in order to apply extra filtering
     * by MIME type; if no images are found but videos exist we return the
     * sentinel value.  The result is cached via the helper provided by the
     * base class.
     *
     * @return array<string>
     */
    public function getState(): array
    {
        return $this->cacheState(function (): array {
            $record = $this->getRecord();

            if ($this->hasRelationship($record)) {
                $record = $this->getRelationshipResults($record);
            }

            $records = Arr::wrap($record);
            $state   = [];
            $collection = $this->getCollection() ?? 'default';

            foreach ($records as $record) {
                /** @var Model $record */
                $mediaCollection = $record->getRelationValue('media')
                    ->when(
                        ! $collection instanceof AllMediaCollections,
                        fn (MediaCollection $mediaCollection) => $mediaCollection->filter(fn (Media $media): bool => $media->getAttributeValue('collection_name') === $collection),
                    )
                    ->when(
                        $this->hasMediaFilter(),
                        fn (Collection $media) => $this->filterMedia($media),
                    );

                // only look at images for the normal state
                $images = $mediaCollection->filter(fn (Media $media): bool => str_starts_with($media->mime_type, 'image/'));

                if ($images->isNotEmpty()) {
                    $state = [
                        ...$state,
                        ...$images
                            ->sortBy('order_column')
                            ->pluck('uuid')
                            ->all(),
                    ];

                    continue;
                }

                // if there were no images we still want to know if any videos
                // exist so we can return the sentinel.
                $hasVideo = $mediaCollection->contains(fn (Media $media): bool => str_starts_with($media->mime_type, 'video/'));

                if ($hasVideo && empty($state)) {
                    return [self::VIDEO_ONLY_SENTINEL];
                }
            }

            return array_unique($state);
        });
    }

    /**
     * {@inheritdoc}
     *
     * When the state is the sentinel value we return the placeholder URL
     * instead of attempting to resolve a real media record.
     */
    public function getImageUrl(?string $state = null): ?string
    {
        if ($state === self::VIDEO_ONLY_SENTINEL) {
            return $this->getVideoPlaceholderUrl();
        }

        return parent::getImageUrl($state);
    }
}

<?php

namespace Tests\Unit;

use App\Filament\Tables\Columns\MediaColumn;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class MediaColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_state_filters_out_videos()
    {
        $campaign = Campaign::factory()->create();

        // create a dummy table instance so that the column thinks it's mounted
        $fakeLivewire = $this->createMock(HasTable::class);
        $fakeTable = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeTable->method('getLivewire')->willReturn($fakeLivewire);

        $imageMedia = Media::create([
            'model_type' => Campaign::class,
            'model_id' => $campaign->id,
            'collection_name' => 'media',
            'name' => 'photo.jpg',
            'file_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'size' => 100,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'uuid' => (string) Str::uuid(),
        ]);

        Media::create([
            'model_type' => Campaign::class,
            'model_id' => $campaign->id,
            'collection_name' => 'media',
            'name' => 'clip.mp4',
            'file_name' => 'clip.mp4',
            'mime_type' => 'video/mp4',
            'disk' => 'public',
            'size' => 150,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'uuid' => (string) Str::uuid(),
        ]);

        $state = MediaColumn::make('media')
            ->collection('media')
            ->table($fakeTable)
            ->record($campaign)
            ->getState();

        $this->assertSame([$imageMedia->uuid], $state);
    }

    public function test_video_only_returns_sentinel_and_placeholder()
    {
        $campaign = Campaign::factory()->create();

        // create a dummy table instance so that the column thinks it's mounted
        $fakeLivewire = $this->createMock(HasTable::class);
        $fakeTable = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeTable->method('getLivewire')->willReturn($fakeLivewire);

        Media::create([
            'model_type' => Campaign::class,
            'model_id' => $campaign->id,
            'collection_name' => 'media',
            'name' => 'clip.mp4',
            'file_name' => 'clip.mp4',
            'mime_type' => 'video/mp4',
            'disk' => 'public',
            'size' => 150,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'uuid' => (string) Str::uuid(),
        ]);

        $column = MediaColumn::make('media')
            ->collection('media')
            ->table($fakeTable)
            ->record($campaign);

        $state = $column->getState();
        $this->assertSame([MediaColumn::VIDEO_ONLY_SENTINEL], $state);

        $url = $column->getImageUrl(MediaColumn::VIDEO_ONLY_SENTINEL);

        // by default the placeholder is an inline svg, so the returned URL
        // should start with a data URI.  callers are free to override the
        // placeholder via `videoPlaceholderUrl()` which would still satisfy
        // this check.
        $this->assertStringStartsWith('data:image/svg+xml', $url);
    }
}

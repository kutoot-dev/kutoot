<?php

use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    $this->actingAs($user);
    $this->user = $user;
});

it('can load the edit campaign page', function () {
    $campaign = Campaign::factory()->create(['creator_id' => $this->user->id]);

    Livewire::test(EditCampaign::class, ['record' => $campaign->getRouteKey()])
        ->assertOk();
});

it('registers media collection on campaign model', function () {
    $campaign = Campaign::factory()->create();

    $collections = collect($campaign->getRegisteredMediaCollections());

    expect($collections->pluck('name')->toArray())->toContain('media');
});

it('registers media conversions on campaign model', function () {
    $campaign = Campaign::factory()->create();

    $image = UploadedFile::fake()->image('test.jpg', 800, 600);
    $media = $campaign->addMedia($image)->toMediaCollection('media');

    expect($media)->not->toBeNull();
    expect($media->hasGeneratedConversion('thumb'))->toBeTrue();
});

it('allows multiple media files in campaign media collection', function () {
    $campaign = Campaign::factory()->create();

    $image1 = UploadedFile::fake()->image('img1.jpg', 400, 400);
    $image2 = UploadedFile::fake()->image('img2.png', 600, 600);

    $campaign->addMedia($image1)->toMediaCollection('media');
    $campaign->addMedia($image2)->toMediaCollection('media');

    expect($campaign->getMedia('media'))->toHaveCount(2);
});

it('requires at least one image when media contains only videos', function () {
    $video = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

    $rule = function ($attribute, $value, $fail) {
        if (is_array($value) && count($value) > 0) {
            $hasImage = collect($value)->contains(fn($file) => Str::startsWith($file->getClientMimeType(), 'image/'));
            if (! $hasImage) {
                $fail('At least one image is required; videos will play on hover.');
            }
        }
    };

    $validator = Validator::make(
        ['media' => [$video]],
        ['media' => [$rule]]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('media'))->toContain('At least one image is required');

    // also assert that having an image passes
    $image = UploadedFile::fake()->image('good.jpg');
    $validator2 = Validator::make(
        ['media' => [$image]],
        ['media' => [$rule]]
    );

    expect($validator2->passes())->toBeTrue();
});

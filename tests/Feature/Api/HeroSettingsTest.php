<?php

use App\Models\HeroSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the active hero setting for the default app locale and honours the locale query parameter', function () {
    // active english setting and an extra inactive record
    HeroSetting::create([
        'title' => 'English Title',
        'description' => 'English description',
        'locale' => 'en',
        'is_active' => true,
    ]);

    HeroSetting::create([
        'title' => 'Old English Title',
        'description' => 'Stale description',
        'locale' => 'en',
        'is_active' => false,
    ]);

    // active hindi setting
    HeroSetting::create([
        'title' => 'हिंदी शीर्षक',
        'description' => 'Hindi description',
        'locale' => 'hi',
        'is_active' => true,
    ]);

    // call without locale; should default to config('app.locale') which is `en`
    $this->getJson('/api/v1/hero-settings')
        ->assertSuccessful()
        ->assertJsonPath('data.locale', config('app.locale'))
        ->assertJsonPath('data.title', 'English Title')
        ->assertJsonPath('data.is_active', true);

    // ask explicitly for Hindi
    $this->getJson('/api/v1/hero-settings?locale=hi')
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'हिंदी शीर्षक');
});

it('applies a default locale when none is provided', function () {
    // create a setting without specifying locale; the database default should fill in
    HeroSetting::create([
        'title' => 'Default Locale Title',
        'description' => 'Foo bar',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('hero_settings', [
        'title' => 'Default Locale Title',
        'locale' => config('app.locale'),
    ]);
});

it('returns null payload when no setting exists for a given locale', function () {
    // only an english record exists
    HeroSetting::create([
        'title' => 'English Title',
        'description' => 'English description',
        'locale' => 'en',
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/hero-settings?locale=fr')
        ->assertSuccessful()
        ->assertJson([
            'data' => null,
        ]);
});

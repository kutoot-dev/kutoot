<?php

use App\Models\MerchantCategory;
use App\Models\MerchantLocation;
use App\Models\Merchant;
use App\Models\Sponsor;
use App\Models\Tag;

use function Pest\Laravel\getJson;

// ── Merchant Categories ──────────────────────────────────────────────────

it('returns active merchant categories publicly without auth', function () {
    MerchantCategory::factory()->count(3)->create(['is_active' => true]);
    MerchantCategory::factory()->create(['is_active' => false]);

    getJson('/api/v1/store-categories')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'image', 'icon', 'serial'],
            ],
        ]);
});

it('returns empty array when no active merchant categories exist', function () {
    getJson('/api/v1/store-categories')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('filters merchant categories by search query', function () {
    MerchantCategory::factory()->create(['name' => 'Restaurant', 'is_active' => true]);
    MerchantCategory::factory()->create(['name' => 'Grocery', 'is_active' => true]);

    getJson('/api/v1/store-categories?search=rest')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Restaurant');
});

// ── Stores by Category ──────────────────────────────────────────────────

it('returns merchant locations by category', function () {
    $category = MerchantCategory::factory()->create(['is_active' => true]);
    $merchant = Merchant::factory()->create();

    MerchantLocation::factory()->count(3)->create([
        'merchant_id' => $merchant->id,
        'merchant_category_id' => $category->id,
        'is_active' => true,
    ]);

    getJson("/api/v1/store-categories/{$category->id}/stores")
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('does not return inactive merchant locations', function () {
    $category = MerchantCategory::factory()->create(['is_active' => true]);
    $merchant = Merchant::factory()->create();

    MerchantLocation::factory()->create([
        'merchant_id' => $merchant->id,
        'merchant_category_id' => $category->id,
        'is_active' => true,
    ]);
    MerchantLocation::factory()->create([
        'merchant_id' => $merchant->id,
        'merchant_category_id' => $category->id,
        'is_active' => false,
    ]);

    getJson("/api/v1/store-categories/{$category->id}/stores")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('filters merchant locations by tags', function () {
    $category = MerchantCategory::factory()->create(['is_active' => true]);
    $merchant = Merchant::factory()->create();
    $tag = Tag::factory()->create(['name' => 'vegan']);

    $matched = MerchantLocation::factory()->create([
        'merchant_id' => $merchant->id,
        'merchant_category_id' => $category->id,
        'is_active' => true,
    ]);
    $matched->tags()->attach($tag);

    MerchantLocation::factory()->create([
        'merchant_id' => $merchant->id,
        'merchant_category_id' => $category->id,
        'is_active' => true,
    ]);

    getJson("/api/v1/store-categories/{$category->id}/stores?tags={$tag->id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('searches merchant locations by branch name', function () {
    $category = MerchantCategory::factory()->create(['is_active' => true]);
    $merchant = Merchant::factory()->create();

    MerchantLocation::factory()->create([
        'merchant_id' => $merchant->id,
        'merchant_category_id' => $category->id,
        'branch_name' => 'Downtown Pizza',
        'is_active' => true,
    ]);
    MerchantLocation::factory()->create([
        'merchant_id' => $merchant->id,
        'merchant_category_id' => $category->id,
        'branch_name' => 'Uptown Sushi',
        'is_active' => true,
    ]);

    getJson("/api/v1/store-categories/{$category->id}/stores?search=pizza")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.branch_name', 'Downtown Pizza');
});

it('returns 404 for non-existent category', function () {
    getJson('/api/v1/store-categories/99999/stores')
        ->assertNotFound();
});

// ── Sponsors ──────────────────────────────────────────────────────────────

it('returns active sponsors publicly without auth', function () {
    Sponsor::factory()->count(2)->create(['is_active' => true]);
    Sponsor::factory()->create(['is_active' => false]);

    getJson('/api/v1/sponsors')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'type', 'logo', 'banner', 'link'],
            ],
        ]);
});

it('returns empty array when no active sponsors exist', function () {
    getJson('/api/v1/sponsors')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

// ── Tags ──────────────────────────────────────────────────────────────────

it('returns all tags publicly without auth', function () {
    Tag::factory()->count(5)->create();

    getJson('/api/v1/tags')
        ->assertSuccessful()
        ->assertJsonCount(5, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name'],
            ],
        ]);
});

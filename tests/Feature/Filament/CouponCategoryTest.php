<?php

use App\Filament\Resources\CouponCategories\Pages\CreateCouponCategory;
use App\Models\CouponCategory;
use App\Models\User;
use function Pest\Livewire\livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    $this->actingAs($user);
});

it('can auto-generate a slug from the name', function () {
    livewire(CreateCouponCategory::class)
        ->fillForm([
            'name' => 'Test Category',
        ])
        ->assertFormSet([
            'slug' => 'test-category',
        ]);
});

it('validates that the slug is unique', function () {
    CouponCategory::factory()->create([
        'slug' => 'test-slug',
    ]);

    livewire(CreateCouponCategory::class)
        ->fillForm([
            'name' => 'Another Name',
            'slug' => 'test-slug',
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('can create a coupon category with a unique slug', function () {
    livewire(CreateCouponCategory::class)
        ->fillForm([
            'name' => 'Unique Category',
            'slug' => 'unique-slug',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(CouponCategory::class, [
        'name' => 'Unique Category',
        'slug' => 'unique-slug',
    ]);
});

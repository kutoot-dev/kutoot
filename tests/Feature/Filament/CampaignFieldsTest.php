<?php

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\User;
use App\Enums\CampaignStatus;
use App\Enums\CreatorType;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    $this->actingAs($user);
});

it('can create a campaign without cache fields', function () {
    $category = CampaignCategory::factory()->create();
    $creator = User::factory()->create();

    Livewire::test(CreateCampaign::class)
        ->fillForm([
            'category_id' => $category->id,
            'creator_type' => CreatorType::Merchant->value,
            'creator_id' => $creator->id,
            'reward_name' => 'Test Reward',
            'status' => CampaignStatus::Active->value,
            'start_date' => now()->toDateString(),
            'reward_cost_target' => 100,
            'stamp_target' => 5,
            // 'collected_commission_cache' => null, // Left empty
            // 'issued_stamps_cache' => null, // Left empty
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Campaign::class, [
        'reward_name' => 'Test Reward',
        'collected_commission_cache' => 0,
        'issued_stamps_cache' => 0,
    ]);
});

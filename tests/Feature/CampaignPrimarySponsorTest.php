<?php

use App\Models\Campaign;
use App\Models\Sponsor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // make sure migrations have run
});

it('syncPrimarySponsorPivot updates the pivot table flags and respects the attribute', function () {
    $campaign = Campaign::factory()->create();

    $s1 = Sponsor::factory()->create();
    $s2 = Sponsor::factory()->create();

    // attach both sponsors without any primary flag
    $campaign->sponsors()->attach([$s1->id, $s2->id]);

    expect($campaign->sponsors()->wherePivot('is_primary', true)->count())->toBe(0);

    // set a primary sponsor id and save
    $campaign->primary_sponsor_id = $s2->id;
    $campaign->save();

    // the attribute should be stored and accessible
    $campaign->refresh();
    expect($campaign->primary_sponsor_id)->toBe($s2->id);

    // the helper method should return the correct sponsor
    expect($campaign->primarySponsor()->id)->toBe($s2->id);

    // pivot table must have been updated as well
    $primary = $campaign->sponsors()->wherePivot('is_primary', true)->first();
    expect($primary->id)->toBe($s2->id);

    // clearing the attribute should wipe the flag
    $campaign->primary_sponsor_id = null;
    $campaign->save();
    $campaign->refresh();
    expect($campaign->primarySponsor())->toBeNull();
    expect($campaign->sponsors()->wherePivot('is_primary', true)->exists())->toBeFalse();
});

it('primarySponsor falls back to pivot when attribute is null', function () {
    $campaign = Campaign::factory()->create();
    $s1 = Sponsor::factory()->create();

    $campaign->sponsors()->attach($s1->id, ['is_primary' => true]);

    expect($campaign->primary_sponsor_id)->toBeNull();
    expect($campaign->primarySponsor()->id)->toBe($s1->id);
});

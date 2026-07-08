<?php

use App\Models\Tier;

beforeEach(function () {
    config()->set('discord.internal_api_secret', 'test-secret');
});

/**
 * @return array<string, string>
 */
function tierAuth(): array
{
    return ['Authorization' => 'Bearer test-secret'];
}

it('seeds the default tiers', function () {
    expect(Tier::where('name', 'protector')->value('points'))->toBe(800);
    expect(Tier::where('name', 'gold')->value('points'))->toBe(500);
    expect(Tier::count())->toBe(6);
});

it('lists tiers ordered by points descending', function () {
    $response = $this->withHeaders(tierAuth())->getJson('/api/tiers')->assertOk();

    expect($response->json('data.0.name'))->toBe('protector');
    expect($response->json('data.0.points'))->toBe(800);
});

it('adds and updates a tier by name', function () {
    $this->withHeaders(tierAuth())
        ->postJson('/api/tiers', ['name' => 'Diamond', 'points' => 1000])
        ->assertCreated()
        ->assertJsonPath('data.points', 1000);

    // Same name updates the points.
    $this->withHeaders(tierAuth())
        ->postJson('/api/tiers', ['name' => 'diamond', 'points' => 1200])
        ->assertCreated();

    expect(Tier::where('name', 'diamond')->count())->toBe(1);
    expect(Tier::where('name', 'diamond')->value('points'))->toBe(1200);
});

it('removes a tier', function () {
    $tier = Tier::where('name', 'brown')->first();

    $this->withHeaders(tierAuth())->deleteJson("/api/tiers/{$tier->id}")->assertOk();

    expect(Tier::where('name', 'brown')->exists())->toBeFalse();
});

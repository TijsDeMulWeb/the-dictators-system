<?php

use App\Models\Player;
use App\Services\PlayerSyncService;

it('deactivates players who are no longer in the guild on a full sync', function () {
    $staying = Player::factory()->create(['discord_id' => 'A', 'is_active' => true]);
    $leaving = Player::factory()->create(['discord_id' => 'B', 'is_active' => true]);

    (new PlayerSyncService)->sync([
        ['discord_id' => 'A', 'username' => 'a', 'display_name' => 'A'],
    ]);

    expect($staying->fresh()->is_active)->toBeTrue();
    expect($leaving->fresh()->is_active)->toBeFalse();
});

it('reactivates a returning member and keeps their history', function () {
    Player::factory()->create(['discord_id' => 'A', 'is_active' => false]);

    (new PlayerSyncService)->sync([
        ['discord_id' => 'A', 'username' => 'a', 'display_name' => 'A'],
    ]);

    expect(Player::where('discord_id', 'A')->first()->is_active)->toBeTrue();
});

it('upsert does not deactivate anyone else', function () {
    $other = Player::factory()->create(['discord_id' => 'A', 'is_active' => true]);

    (new PlayerSyncService)->upsert([
        ['discord_id' => 'B', 'username' => 'b', 'display_name' => 'B'],
    ]);

    expect($other->fresh()->is_active)->toBeTrue();
    expect(Player::where('discord_id', 'B')->first()->is_active)->toBeTrue();
});

it('excludes inactive players from the selectable list', function () {
    config()->set('discord.internal_api_secret', 'test-secret');

    Player::factory()->create(['display_name' => 'Active', 'is_active' => true]);
    Player::factory()->create(['display_name' => 'Left', 'is_active' => false]);

    $response = $this->withHeaders(['Authorization' => 'Bearer test-secret'])
        ->getJson('/api/players')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.display_name'))->toBe('Active');
});

<?php

use App\Enums\ReportStatus;
use App\Models\Player;
use App\Models\Report;

beforeEach(function () {
    config()->set('discord.internal_api_secret', 'test-secret');
});

/**
 * @return array<string, string>
 */
function botHeaders(): array
{
    return ['Authorization' => 'Bearer test-secret'];
}

it('rejects requests without the bot secret', function () {
    $this->postJson('/api/players/sync', ['members' => []])
        ->assertUnauthorized();
});

it('syncs guild members into players', function () {
    $this->withHeaders(botHeaders())
        ->postJson('/api/players/sync', [
            'members' => [
                ['discord_id' => '111', 'username' => 'alice', 'display_name' => 'Alice', 'is_retired' => false],
                ['discord_id' => '222', 'username' => 'bob', 'display_name' => 'Bob', 'is_retired' => true],
            ],
        ])
        ->assertOk()
        ->assertJson(['synced' => 2]);

    expect(Player::count())->toBe(2);
});

it('only lists non-retired players for selection by default', function () {
    Player::factory()->create(['display_name' => 'Active']);
    Player::factory()->retired()->create(['display_name' => 'Retired']);

    $response = $this->withHeaders(botHeaders())->getJson('/api/players')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.display_name'))->toBe('Active');
});

it('creates a pending report with per-player points and a sequential number', function () {
    Player::factory()->create(['discord_id' => 'L1']);
    Player::factory()->create(['discord_id' => 'P2']);

    $response = $this->withHeaders(botHeaders())
        ->postJson('/api/reports', [
            'leader_discord_id' => 'L1',
            'game' => 'Asia',
            'day' => 40,
            'result' => 'win',
            'players' => [
                ['discord_id' => 'L1', 'country' => 'Iran', 'points' => 550],
                ['discord_id' => 'P2', 'country' => 'Pakistan', 'points' => 1300],
            ],
        ])
        ->assertCreated();

    expect($response->json('data.report_number'))->toBe(1);
    expect($response->json('data.status'))->toBe('pending');
    expect($response->json('data.players'))->toHaveCount(2);

    $report = Report::first();
    expect($report->players()->where('discord_id', 'P2')->first()->pivot->points)->toBe(1300);
});

it('starts numbering at the configured start number', function () {
    config()->set('discord.report_start_number', 576);
    Player::factory()->create(['discord_id' => 'L1']);

    $this->withHeaders(botHeaders())
        ->postJson('/api/reports', [
            'leader_discord_id' => 'L1',
            'result' => 'win',
            'players' => [['discord_id' => 'L1', 'points' => 500]],
        ])
        ->assertCreated()
        ->assertJsonPath('data.report_number', 576);

    // The next report continues from there.
    Player::factory()->create(['discord_id' => 'L2']);
    $this->withHeaders(botHeaders())
        ->postJson('/api/reports', [
            'leader_discord_id' => 'L2',
            'result' => 'win',
            'players' => [['discord_id' => 'L2', 'points' => 500]],
        ])
        ->assertCreated()
        ->assertJsonPath('data.report_number', 577);
});

it('rounds submitted points to the nearest 50', function () {
    Player::factory()->create(['discord_id' => 'L1']);

    $this->withHeaders(botHeaders())
        ->postJson('/api/reports', [
            'leader_discord_id' => 'L1',
            'result' => 'win',
            'players' => [
                ['discord_id' => 'L1', 'points' => 1720],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.players.0.points', 1700);
});

it('approves and rejects reports and blocks double reviews', function () {
    $report = Report::factory()->create();

    $this->withHeaders(botHeaders())
        ->postJson("/api/reports/{$report->id}/approve", ['reviewer_discord_id' => 'SEC1'])
        ->assertOk()
        ->assertJson(['data' => ['status' => 'approved']]);

    expect($report->fresh()->status)->toBe(ReportStatus::Approved);

    $this->withHeaders(botHeaders())
        ->postJson("/api/reports/{$report->id}/reject", ['reviewer_discord_id' => 'SEC1'])
        ->assertStatus(422);
});

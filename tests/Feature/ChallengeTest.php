<?php

use App\Enums\ChallengeTier;
use App\Models\Challenge;
use App\Models\Player;
use App\Services\ReportService;
use App\Services\ScoreboardService;

beforeEach(function () {
    config()->set('discord.internal_api_secret', 'test-secret');
});

/**
 * @return array<string, string>
 */
function botAuth(): array
{
    return ['Authorization' => 'Bearer test-secret'];
}

it('derives fixed points from the tier', function () {
    expect(ChallengeTier::Protector->points())->toBe(800);
    expect(ChallengeTier::Gold->points())->toBe(500);
    expect(ChallengeTier::Brown->points())->toBe(200);
});

it('creates a challenge report that snapshots the bonus', function () {
    Player::factory()->create(['discord_id' => 'L1']);
    $challenge = Challenge::factory()->create(['tier' => 'gold', 'points' => 500]);

    $report = app(ReportService::class)->createFromBot([
        'leader_discord_id' => 'L1',
        'result' => 'win',
        'challenge_id' => $challenge->id,
        'players' => [['discord_id' => 'L1', 'points' => 1000]],
    ]);

    expect($report->challenge_id)->toBe($challenge->id);
    expect($report->challenge_bonus)->toBe(500);
});

it('adds the challenge bonus on top of game points in the scoreboard', function () {
    $player = Player::factory()->create(['discord_id' => 'P1', 'display_name' => 'P1']);
    $challenge = Challenge::factory()->create(['tier' => 'gold', 'points' => 500]);

    app(ReportService::class)->createFromBot([
        'leader_discord_id' => 'P1',
        'result' => 'win',
        'challenge_id' => $challenge->id,
        'players' => [['discord_id' => 'P1', 'points' => 1000]],
    ])->update(['status' => 'approved']);

    $row = (new ScoreboardService)->build()->firstWhere('player_id', $player->id);

    // 1000 entered + 500 challenge bonus = 1500 total.
    expect($row['total_points'])->toBe(1500);
    expect($row['avg_points'])->toBe(1500.0);
});

it('manages challenges through the api', function () {
    $created = $this->withHeaders(botAuth())
        ->postJson('/api/challenges', ['name' => 'Protector', 'tier' => 'gold'])
        ->assertCreated()
        ->json('data');

    expect($created['points'])->toBe(500);

    $this->withHeaders(botAuth())->getJson('/api/challenges')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Protector');

    $this->withHeaders(botAuth())->deleteJson("/api/challenges/{$created['id']}")->assertOk();

    $this->withHeaders(botAuth())->getJson('/api/challenges')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

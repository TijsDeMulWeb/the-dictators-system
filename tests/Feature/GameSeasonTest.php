<?php

use App\Models\Player;
use App\Models\Report;
use App\Models\Season;
use App\Services\GameService;
use App\Services\ReportService;
use App\Services\ScoreboardService;
use App\Services\SeasonService;
use Illuminate\Validation\ValidationException;

it('seeds a single active season starting at 576', function () {
    $season = app(SeasonService::class)->active();

    expect($season->base)->toBe(500);
    expect($season->next_number)->toBe(576);
    expect($season->lastNumber())->toBe(599);
});

it('assigns the next game number and advances the season', function () {
    $players = Player::factory()->count(2)->create();

    $game = app(GameService::class)->create($players->pluck('discord_id')->all(), 'LEADER');

    expect($game->number)->toBe(576);
    expect($game->players)->toHaveCount(2);
    expect(app(SeasonService::class)->active()->next_number)->toBe(577);
});

it('blocks new games when the season is full', function () {
    Season::query()->where('is_active', true)->update(['next_number' => 600]);
    $player = Player::factory()->create();

    expect(fn () => app(GameService::class)->create([$player->discord_id]))
        ->toThrow(ValidationException::class);
});

it('starts a new season at the next hundred', function () {
    $season = app(SeasonService::class)->startNew();

    expect($season->base)->toBe(600);
    expect($season->next_number)->toBe(600);
    expect(Season::where('is_active', true)->count())->toBe(1);
});

it('snaps the current number to its hundred block', function () {
    $season = app(SeasonService::class)->setCurrentNumber(640);

    expect($season->base)->toBe(600);
    expect($season->next_number)->toBe(640);
});

it('creates a game report using the game number and season, once per game', function () {
    $player = Player::factory()->create(['discord_id' => 'P1']);
    $game = app(GameService::class)->create(['P1'], 'P1');

    $report = app(ReportService::class)->createFromBot([
        'leader_discord_id' => 'P1',
        'game_id' => $game->id,
        'result' => 'win',
        'players' => [['discord_id' => 'P1', 'points' => 1000]],
    ]);

    expect($report->report_number)->toBe(576);
    expect($report->season_id)->toBe($game->season_id);
    expect($report->game_id)->toBe($game->id);

    // A second report for the same game is rejected.
    expect(fn () => app(ReportService::class)->createFromBot([
        'leader_discord_id' => 'P1',
        'game_id' => $game->id,
        'result' => 'win',
        'players' => [['discord_id' => 'P1', 'points' => 1000]],
    ]))->toThrow(ValidationException::class);
});

it('scores only the active season', function () {
    $player = Player::factory()->create(['display_name' => 'P']);

    // Report in the current (500) season.
    Report::factory()->approved()->win()->for($player, 'leader')->create()
        ->players()->attach($player, ['points' => 1000]);

    // Report in an old season -> excluded from the active-season scoreboard.
    $old = Season::query()->create(['base' => 400, 'next_number' => 500, 'is_active' => false]);
    Report::factory()->approved()->win()->state(['season_id' => $old->id])->for($player, 'leader')->create()
        ->players()->attach($player, ['points' => 9999]);

    $board = (new ScoreboardService)->build();
    $row = $board->firstWhere('player_id', $player->id);

    expect($row['games'])->toBe(1);
    expect($row['total_points'])->toBe(1000);
});

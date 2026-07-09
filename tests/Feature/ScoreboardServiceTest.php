<?php

use App\Models\Player;
use App\Models\Report;
use App\Services\ScoreboardService;

/**
 * Attach a player to an approved report with the given points.
 */
function seedGames(Player $player, int $games, int $wins, int $pointsPerGame): void
{
    for ($i = 0; $i < $games; $i++) {
        $report = Report::factory()->approved()
            ->state(['result' => $i < $wins ? 'win' : 'loss'])
            ->for($player, 'leader')
            ->create();

        $report->players()->attach($player, ['points' => $pointsPerGame]);
    }
}

it('counts a won game as a personal loss when the player scored under 500', function () {
    $player = Player::factory()->create(['display_name' => 'Underperformer']);

    // Won game but only 300 points -> personal loss.
    $lowWin = Report::factory()->approved()->win()->for($player, 'leader')->create();
    $lowWin->players()->attach($player, ['points' => 300]);

    // Won game with 500 points -> personal win (threshold is inclusive).
    $goodWin = Report::factory()->approved()->win()->for($player, 'leader')->create();
    $goodWin->players()->attach($player, ['points' => 500]);

    $row = (new ScoreboardService)->build()->firstWhere('player_id', $player->id);

    expect($row['games'])->toBe(2);
    expect($row['wins'])->toBe(1);
    expect($row['losses'])->toBe(1);
    expect($row['win_rate'])->toBe(0.5);
});

it('computes the final score using the natural-log volume formula', function () {
    $service = new ScoreboardService;

    // Ensign Matthewes: 23 games, 23 wins, 40950 total pts -> 17982
    expect(round($service->finalScore(40950, 23, 23)))->toBe(17982.0);

    // Liuetenant Blaize: 20 games, 18 wins, 38280 total pts -> 15967
    expect(round($service->finalScore(38280, 20, 18)))->toBe(15967.0);

    // General Vuk: 18 games, 18 wins, 28850 total pts -> 13896
    expect(round($service->finalScore(28850, 18, 18)))->toBe(13896.0);
});

it('returns zero final score when a player has no games', function () {
    expect((new ScoreboardService)->finalScore(0, 0, 0))->toBe(0.0);
});

it('builds a ranked scoreboard from approved reports only', function () {
    $strong = Player::factory()->create(['display_name' => 'Strong']);
    $weak = Player::factory()->create(['display_name' => 'Weak']);

    seedGames($strong, games: 10, wins: 10, pointsPerGame: 1500);
    seedGames($weak, games: 4, wins: 2, pointsPerGame: 500);

    // A pending report must not count toward the scoreboard.
    $pending = Report::factory()->win()->for($strong, 'leader')->create();
    $pending->players()->attach($strong, ['points' => 99999]);

    $board = (new ScoreboardService)->build();

    expect($board)->toHaveCount(2);
    expect($board->first()['name'])->toBe('Strong');
    expect($board->first()['rank'])->toBe(1);
    expect($board->first()['games'])->toBe(10);
    expect($board->first()['wins'])->toBe(10);
    expect($board->first()['win_rate'])->toBe(1.0);
    expect($board->last()['name'])->toBe('Weak');
    expect($board->last()['win_rate'])->toBe(0.5);
});

it('can exclude retired players from the scoreboard', function () {
    $active = Player::factory()->create(['display_name' => 'Active']);
    $retired = Player::factory()->retired()->create(['display_name' => 'Retired']);

    seedGames($active, games: 3, wins: 3, pointsPerGame: 1000);
    seedGames($retired, games: 3, wins: 3, pointsPerGame: 1000);

    $board = (new ScoreboardService)->build(includeRetired: false);

    expect($board)->toHaveCount(1);
    expect($board->first()['name'])->toBe('Active');
});

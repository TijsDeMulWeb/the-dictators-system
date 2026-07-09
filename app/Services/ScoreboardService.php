<?php

namespace App\Services;

use App\Enums\ReportResult;
use App\Enums\ReportStatus;
use App\Models\Player;
use Illuminate\Support\Collection;

class ScoreboardService
{
    /**
     * Build the ranked scoreboard from all approved reports.
     *
     * Final score = avg points × (ln(games + 1))² × win rate,
     * where win rate is a fraction between 0 and 1.
     *
     * @return Collection<int, array{
     *     player_id: int,
     *     name: string,
     *     games: int,
     *     wins: int,
     *     losses: int,
     *     total_points: int,
     *     avg_points: float,
     *     win_rate: float,
     *     final_score: float,
     *     rank: int
     * }>
     */
    public function build(bool $includeRetired = true): Collection
    {
        $players = Player::query()
            ->when(! $includeRetired, fn ($query) => $query->where('is_retired', false))
            ->with(['reports' => function ($query) {
                $query->where('reports.status', ReportStatus::Approved->value);
            }])
            ->get();

        return $players
            ->map(function (Player $player) {
                $reports = $player->reports;
                $games = $reports->count();

                if ($games === 0) {
                    return null;
                }

                // A game only counts as a personal win if the team won AND the
                // player scored at least the win threshold (game points, before
                // any challenge bonus). Otherwise it's a personal loss.
                $threshold = (int) config('discord.win_points_threshold', 500);

                $wins = $reports->filter(
                    fn ($report) => $report->result === ReportResult::Win
                        && (int) $report->pivot->points >= $threshold
                )->count();
                $losses = $games - $wins;

                // Each participant's points for a game include the challenge
                // bonus (if the game was a challenge).
                $totalPoints = (int) $reports->sum(
                    fn ($report) => (int) $report->pivot->points + (int) $report->challenge_bonus
                );

                return [
                    'player_id' => $player->id,
                    'name' => $player->display_name,
                    'games' => $games,
                    'wins' => $wins,
                    'losses' => $losses,
                    'total_points' => $totalPoints,
                    'avg_points' => $this->averagePoints($totalPoints, $games),
                    'win_rate' => (float) ($wins / $games),
                    'final_score' => $this->finalScore($totalPoints, $games, $wins),
                ];
            })
            ->filter()
            ->sortByDesc('final_score')
            ->values()
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    public function finalScore(int $totalPoints, int $games, int $wins): float
    {
        if ($games === 0) {
            return 0.0;
        }

        $avgPoints = $this->averagePoints($totalPoints, $games);
        $winRate = $wins / $games;
        $volume = log($games + 1) ** 2;

        return $avgPoints * $volume * $winRate;
    }

    private function averagePoints(int $totalPoints, int $games): float
    {
        return $games === 0 ? 0.0 : $totalPoints / $games;
    }
}

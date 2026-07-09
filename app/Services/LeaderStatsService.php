<?php

namespace App\Services;

use App\Enums\ReportResult;
use App\Enums\ReportStatus;
use App\Models\Player;
use Illuminate\Support\Collection;

class LeaderStatsService
{
    /**
     * How many games each leader has led (based on approved reports),
     * ranked from most to least.
     *
     * @return Collection<int, array{
     *     player_id: int,
     *     name: string,
     *     games: int,
     *     wins: int,
     *     losses: int,
     *     rank: int
     * }>
     */
    public function build(?int $seasonId = null): Collection
    {
        $seasonId ??= app(SeasonService::class)->active()->id;

        return Player::query()
            ->with(['ledReports' => function ($query) use ($seasonId) {
                $query->where('status', ReportStatus::Approved->value)
                    ->where('season_id', $seasonId);
            }])
            ->get()
            ->map(function (Player $leader) {
                $reports = $leader->ledReports;
                $games = $reports->count();

                if ($games === 0) {
                    return null;
                }

                $wins = $reports->where('result', ReportResult::Win)->count();

                return [
                    'player_id' => $leader->id,
                    'name' => $leader->display_name,
                    'games' => $games,
                    'wins' => $wins,
                    'losses' => $games - $wins,
                ];
            })
            ->filter()
            ->sortByDesc('games')
            ->values()
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;

                return $row;
            });
    }
}

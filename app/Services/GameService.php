<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameService
{
    public function __construct(private readonly SeasonService $seasons) {}

    /**
     * Start a new game in the active season, assigning the next number and
     * attaching the given players.
     *
     * @param  array<int, string>  $playerDiscordIds
     */
    public function create(array $playerDiscordIds, ?string $createdByDiscordId = null): Game
    {
        return DB::transaction(function () use ($playerDiscordIds, $createdByDiscordId): Game {
            $season = $this->seasons->active();

            if ($season->isFull()) {
                throw ValidationException::withMessages([
                    'season' => "Season {$season->base}–{$season->lastNumber()} is full. Run /new-season to start the next one.",
                ]);
            }

            $game = Game::query()->create([
                'number' => $season->next_number,
                'season_id' => $season->id,
                'created_by_discord_id' => $createdByDiscordId,
            ]);

            $playerIds = Player::query()
                ->whereIn('discord_id', $playerDiscordIds)
                ->pluck('id')
                ->all();

            $game->players()->sync($playerIds);

            $season->increment('next_number');

            return $game->fresh(['players', 'season']);
        });
    }

    public function setChannel(Game $game, string $channelId): Game
    {
        $game->update(['channel_id' => $channelId]);

        return $game;
    }

    public function findByChannel(string $channelId): ?Game
    {
        return Game::query()->where('channel_id', $channelId)->with(['players', 'season', 'report'])->first();
    }
}

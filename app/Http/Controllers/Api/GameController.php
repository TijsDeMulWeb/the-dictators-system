<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function __construct(private readonly GameService $games) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_discord_ids' => ['required', 'array', 'min:1'],
            'player_discord_ids.*' => ['string'],
            'created_by_discord_id' => ['nullable', 'string'],
        ]);

        $game = $this->games->create(
            $validated['player_discord_ids'],
            $validated['created_by_discord_id'] ?? null,
        );

        return response()->json(['data' => $this->transform($game)], 201);
    }

    public function setChannel(Request $request, Game $game): JsonResponse
    {
        $validated = $request->validate([
            'channel_id' => ['required', 'string'],
        ]);

        $game = $this->games->setChannel($game, $validated['channel_id']);

        return response()->json(['data' => $this->transform($game->load(['players', 'season']))]);
    }

    /**
     * Look up the game tied to a Discord channel (used by /report).
     */
    public function byChannel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel_id' => ['required', 'string'],
        ]);

        $game = $this->games->findByChannel($validated['channel_id']);

        if (! $game) {
            return response()->json(['message' => 'No game for this channel.'], 404);
        }

        return response()->json([
            'data' => [
                ...$this->transform($game),
                'has_report' => $game->report()->exists(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Game $game): array
    {
        return [
            'id' => $game->id,
            'number' => $game->number,
            'channel_id' => $game->channel_id,
            'season' => [
                'base' => $game->season->base,
                'last_number' => $game->season->lastNumber(),
            ],
            'players' => $game->players->map(fn ($player) => [
                'discord_id' => $player->discord_id,
                'display_name' => $player->display_name,
            ])->values(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Services\PlayerSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    /**
     * Return the players available for report selection (non-retired).
     */
    public function index(Request $request): JsonResponse
    {
        $players = Player::query()
            ->where('is_active', true)
            ->when(! $request->boolean('include_retired'), fn ($query) => $query->where('is_retired', false))
            ->orderBy('display_name')
            ->get(['id', 'discord_id', 'username', 'display_name', 'is_retired']);

        return response()->json(['data' => $players]);
    }

    /**
     * Upsert the guild members pushed by the bot.
     */
    public function sync(Request $request, PlayerSyncService $service): JsonResponse
    {
        $validated = $request->validate([
            'members' => ['required', 'array'],
            'members.*.discord_id' => ['required', 'string'],
            'members.*.username' => ['required', 'string'],
            'members.*.display_name' => ['required', 'string'],
            'members.*.avatar_url' => ['nullable', 'string'],
            'members.*.is_retired' => ['boolean'],
        ]);

        $count = $service->sync($validated['members']);

        return response()->json(['synced' => $count]);
    }

    /**
     * Upsert one or more members without deactivating anyone (live events).
     */
    public function upsert(Request $request, PlayerSyncService $service): JsonResponse
    {
        $validated = $request->validate([
            'members' => ['required', 'array'],
            'members.*.discord_id' => ['required', 'string'],
            'members.*.username' => ['required', 'string'],
            'members.*.display_name' => ['required', 'string'],
            'members.*.avatar_url' => ['nullable', 'string'],
            'members.*.is_retired' => ['boolean'],
        ]);

        $count = $service->upsert($validated['members']);

        return response()->json(['upserted' => $count]);
    }

    /**
     * Mark a member who left the guild as inactive.
     */
    public function deactivate(Request $request, PlayerSyncService $service): JsonResponse
    {
        $validated = $request->validate([
            'discord_id' => ['required', 'string'],
        ]);

        $service->deactivate($validated['discord_id']);

        return response()->json(['ok' => true]);
    }
}

<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Facades\DB;

class PlayerSyncService
{
    /**
     * Full sync of the guild roster: upsert everyone present and deactivate any
     * player who is no longer in the pushed list (they left the server).
     *
     * @param  array<int, array{discord_id: string, username: string, display_name: string, avatar_url?: string|null, is_retired?: bool}>  $members
     * @return int Number of players upserted.
     */
    public function sync(array $members): int
    {
        return DB::transaction(function () use ($members): int {
            $count = $this->upsert($members, active: true);

            $presentIds = collect($members)->pluck('discord_id')->map(fn ($id) => (string) $id)->all();

            Player::query()
                ->when($presentIds !== [], fn ($query) => $query->whereNotIn('discord_id', $presentIds))
                ->update(['is_active' => false]);

            return $count;
        });
    }

    /**
     * Upsert one or more members without deactivating anyone else. Used for live
     * member add/update events and as a safety net before saving a report.
     *
     * @param  array<int, array{discord_id: string, username: string, display_name: string, avatar_url?: string|null, is_retired?: bool}>  $members
     */
    public function upsert(array $members, bool $active = true): int
    {
        $now = now();

        $rows = collect($members)->map(fn (array $member): array => [
            'discord_id' => (string) $member['discord_id'],
            'username' => $member['username'],
            'display_name' => $member['display_name'],
            'avatar_url' => $member['avatar_url'] ?? null,
            'is_retired' => (bool) ($member['is_retired'] ?? false),
            'is_active' => $active,
            'synced_at' => $now,
            'updated_at' => $now,
            'created_at' => $now,
        ])->all();

        if ($rows === []) {
            return 0;
        }

        Player::query()->upsert(
            $rows,
            uniqueBy: ['discord_id'],
            update: ['username', 'display_name', 'avatar_url', 'is_retired', 'is_active', 'synced_at', 'updated_at'],
        );

        return count($rows);
    }

    /**
     * Mark a player who left the guild as inactive (kept for history).
     */
    public function deactivate(string $discordId): void
    {
        Player::query()->where('discord_id', $discordId)->update(['is_active' => false]);
    }
}

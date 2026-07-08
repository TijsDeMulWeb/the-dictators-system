<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\Tier;
use Illuminate\Support\Collection;

class ChallengeService
{
    /**
     * Create a challenge (or reactivate/retier one with the same name). Points
     * are snapshotted from the tier at creation time.
     */
    public function create(string $name, Tier $tier): Challenge
    {
        $challenge = Challenge::query()->firstOrNew(['name' => $name]);
        $challenge->tier = $tier->name;
        $challenge->points = $tier->points;
        $challenge->is_active = true;
        $challenge->save();

        return $challenge;
    }

    /**
     * Soft-remove a challenge (kept so historical reports keep their bonus).
     */
    public function remove(Challenge $challenge): void
    {
        $challenge->update(['is_active' => false]);
    }

    /**
     * @return Collection<int, Challenge>
     */
    public function listActive(): Collection
    {
        return Challenge::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}

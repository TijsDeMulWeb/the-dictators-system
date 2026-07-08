<?php

namespace App\Services;

use App\Models\Tier;
use Illuminate\Support\Collection;

class TierService
{
    /**
     * Create a tier or update the points of an existing one (by name).
     */
    public function create(string $name, int $points): Tier
    {
        $tier = Tier::query()->firstOrNew(['name' => $this->normalize($name)]);
        $tier->points = $points;
        $tier->save();

        return $tier;
    }

    public function remove(Tier $tier): void
    {
        $tier->delete();
    }

    /**
     * @return Collection<int, Tier>
     */
    public function listByPoints(): Collection
    {
        return Tier::query()->orderByDesc('points')->orderBy('name')->get();
    }

    public function findByName(string $name): ?Tier
    {
        return Tier::query()->where('name', $this->normalize($name))->first();
    }

    private function normalize(string $name): string
    {
        return strtolower(trim($name));
    }
}

<?php

namespace App\Services;

use App\Models\Season;
use Illuminate\Support\Facades\DB;

class SeasonService
{
    /**
     * The single active season. One is always seeded, but fall back to creating
     * a default 500-based season if somehow none exists.
     */
    public function active(): Season
    {
        return Season::query()->where('is_active', true)->first()
            ?? Season::query()->create(['base' => 500, 'next_number' => 500, 'is_active' => true]);
    }

    /**
     * Start the next season (base + 100) and make it the active one.
     */
    public function startNew(): Season
    {
        return DB::transaction(function (): Season {
            $current = $this->active();
            $nextBase = $current->base + 100;

            $current->update(['is_active' => false]);

            $season = Season::query()->firstOrNew(['base' => $nextBase]);
            $season->next_number = $nextBase;
            $season->is_active = true;
            $season->save();

            return $season;
        });
    }

    /**
     * Set the current game number, snapping the active season to that hundred
     * block (e.g. 640 -> season 600, next number 640).
     */
    public function setCurrentNumber(int $number): Season
    {
        return DB::transaction(function () use ($number): Season {
            $base = intdiv($number, 100) * 100;

            Season::query()->where('is_active', true)->update(['is_active' => false]);

            $season = Season::query()->firstOrNew(['base' => $base]);
            $season->next_number = $number;
            $season->is_active = true;
            $season->save();

            return $season;
        });
    }
}

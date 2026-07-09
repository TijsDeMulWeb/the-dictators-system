<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $base
 * @property int $next_number
 * @property bool $is_active
 */
#[Fillable(['base', 'next_number', 'is_active'])]
class Season extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The highest game number this season can hold (base + 99).
     */
    public function lastNumber(): int
    {
        return $this->base + 99;
    }

    public function isFull(): bool
    {
        return $this->next_number > $this->lastNumber();
    }

    /**
     * @return HasMany<Game, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}

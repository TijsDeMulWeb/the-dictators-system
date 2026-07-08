<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $discord_id
 * @property string $username
 * @property string $display_name
 * @property string|null $avatar_url
 * @property bool $is_retired
 * @property bool $is_active
 * @property Carbon|null $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['discord_id', 'username', 'display_name', 'avatar_url', 'is_retired', 'is_active', 'synced_at'])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_retired' => 'boolean',
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Reports led by this player.
     *
     * @return HasMany<Report, $this>
     */
    public function ledReports(): HasMany
    {
        return $this->hasMany(Report::class, 'leader_id');
    }

    /**
     * Reports this player participated in.
     *
     * @return BelongsToMany<Report, $this>
     */
    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class, 'report_players')
            ->withPivot(['country', 'points'])
            ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $number
 * @property int $season_id
 * @property string|null $channel_id
 * @property string|null $created_by_discord_id
 */
#[Fillable(['number', 'season_id', 'channel_id', 'created_by_discord_id'])]
class Game extends Model
{
    /**
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * @return BelongsToMany<Player, $this>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'game_players')->withTimestamps();
    }

    /**
     * @return HasOne<Report, $this>
     */
    public function report(): HasOne
    {
        return $this->hasOne(Report::class);
    }
}

<?php

namespace App\Models;

use App\Enums\ReportResult;
use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $report_number
 * @property int|null $season_id
 * @property int|null $game_id
 * @property string $game
 * @property int|null $day
 * @property int|null $challenge_id
 * @property int|null $challenge_bonus
 * @property int $leader_id
 * @property ReportResult $result
 * @property string|null $ingame_screenshot_path
 * @property string|null $report_image_path
 * @property ReportStatus $status
 * @property string|null $review_message_id
 * @property string|null $posted_message_id
 * @property string|null $reviewed_by_discord_id
 * @property string|null $review_note
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'report_number', 'season_id', 'game_id', 'game', 'day', 'challenge_id', 'challenge_bonus', 'leader_id', 'result',
    'ingame_screenshot_path', 'report_image_path', 'status',
    'review_message_id', 'posted_message_id', 'reviewed_by_discord_id',
    'review_note', 'reviewed_at',
])]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result' => ReportResult::class,
            'status' => ReportStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'leader_id');
    }

    /**
     * @return BelongsTo<Challenge, $this>
     */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    /**
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * The played game (named to avoid clashing with the `game` map column).
     *
     * @return BelongsTo<Game, $this>
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * @return BelongsToMany<Player, $this>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'report_players')
            ->withPivot(['country', 'points'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ReportPlayer, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ReportPlayer::class);
    }
}

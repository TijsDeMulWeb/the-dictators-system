<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_number' => $this->report_number,
            'game' => $this->game,
            'day' => $this->day,
            'result' => $this->result->value,
            'status' => $this->status->value,
            'challenge' => $this->challenge ? [
                'id' => $this->challenge->id,
                'name' => $this->challenge->name,
                'tier' => $this->challenge->tier,
            ] : null,
            'challenge_bonus' => $this->challenge_bonus,
            'game_id' => $this->game_id,
            'game_channel_id' => $this->gameSession?->channel_id,
            'leader' => [
                'discord_id' => $this->leader->discord_id,
                'display_name' => $this->leader->display_name,
            ],
            'players' => $this->players->map(fn ($player) => [
                'discord_id' => $player->discord_id,
                'display_name' => $player->display_name,
                'country' => $player->pivot->country,
                'points' => (int) $player->pivot->points,
            ])->values(),
            'ingame_screenshot_url' => $this->ingame_screenshot_path
                ? Storage::disk('public')->url($this->ingame_screenshot_path)
                : null,
            'review_note' => $this->review_note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

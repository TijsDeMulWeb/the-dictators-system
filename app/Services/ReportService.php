<?php

namespace App\Services;

use App\Enums\ReportResult;
use App\Enums\ReportStatus;
use App\Models\Challenge;
use App\Models\Player;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportService
{
    /**
     * Create a pending report submitted by a leader through the bot.
     *
     * @param  array{
     *     leader_discord_id: string,
     *     game?: string,
     *     day?: int|null,
     *     challenge_id?: int|null,
     *     result: string,
     *     ingame_screenshot_path?: string|null,
     *     players: array<int, array{discord_id: string, country?: string|null, points?: int}>
     * }  $data
     */
    public function createFromBot(array $data): Report
    {
        return DB::transaction(function () use ($data): Report {
            $leader = Player::query()->where('discord_id', $data['leader_discord_id'])->firstOrFail();

            $challenge = ! empty($data['challenge_id'])
                ? Challenge::query()->findOrFail($data['challenge_id'])
                : null;

            $report = Report::query()->create([
                'report_number' => $this->nextReportNumber(),
                'game' => $data['game'] ?? 'Asia',
                'day' => $data['day'] ?? null,
                'challenge_id' => $challenge?->id,
                'challenge_bonus' => $challenge?->points,
                'leader_id' => $leader->id,
                'result' => ReportResult::from($data['result']),
                'ingame_screenshot_path' => $data['ingame_screenshot_path'] ?? null,
                'status' => ReportStatus::Pending,
            ]);

            foreach ($data['players'] as $entry) {
                $player = Player::query()->where('discord_id', $entry['discord_id'])->firstOrFail();

                $report->players()->attach($player->id, [
                    'country' => $entry['country'] ?? null,
                    'points' => $this->roundToNearestFifty((int) ($entry['points'] ?? 0)),
                ]);
            }

            return $report->fresh(['leader', 'players', 'challenge']);
        });
    }

    public function approve(Report $report, string $reviewerDiscordId): Report
    {
        $this->assertPending($report);

        $report->update([
            'status' => ReportStatus::Approved,
            'reviewed_by_discord_id' => $reviewerDiscordId,
            'reviewed_at' => now(),
        ]);

        return $report;
    }

    public function reject(Report $report, string $reviewerDiscordId, ?string $note = null): Report
    {
        $this->assertPending($report);

        $report->update([
            'status' => ReportStatus::Rejected,
            'reviewed_by_discord_id' => $reviewerDiscordId,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);

        return $report;
    }

    public function nextReportNumber(): int
    {
        return (int) Report::query()->max('report_number') + 1;
    }

    /**
     * Points are always recorded as a multiple of 50 (e.g. 1720 -> 1700).
     */
    public function roundToNearestFifty(int $points): int
    {
        return (int) (round($points / 50) * 50);
    }

    private function assertPending(Report $report): void
    {
        if ($report->status !== ReportStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => "Report #{$report->report_number} is already {$report->status->value}.",
            ]);
        }
    }
}

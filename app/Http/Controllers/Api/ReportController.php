<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    /**
     * Create a pending report submitted by a leader.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leader_discord_id' => ['required', 'string'],
            'game_id' => ['nullable', 'integer', 'exists:games,id'],
            'game' => ['nullable', 'string'],
            'day' => ['nullable', 'integer', 'min:0'],
            'challenge_id' => ['nullable', 'integer', 'exists:challenges,id'],
            'result' => ['required', 'in:win,loss'],
            'ingame_screenshot_url' => ['nullable', 'url'],
            'players' => ['required', 'array', 'min:1'],
            'players.*.discord_id' => ['required', 'string'],
            'players.*.country' => ['nullable', 'string'],
            'players.*.points' => ['required', 'integer'],
        ]);

        if (! empty($validated['ingame_screenshot_url'])) {
            $validated['ingame_screenshot_path'] = $this->storeRemoteImage($validated['ingame_screenshot_url']);
        }

        $report = $this->reports->createFromBot($validated);

        return ReportResource::make($report->load(['leader', 'players', 'challenge']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Report $report): ReportResource
    {
        return ReportResource::make($report->load(['leader', 'players', 'challenge']));
    }

    public function approve(Request $request, Report $report): ReportResource
    {
        $validated = $request->validate([
            'reviewer_discord_id' => ['required', 'string'],
            'posted_message_id' => ['nullable', 'string'],
        ]);

        $report = $this->reports->approve($report, $validated['reviewer_discord_id']);

        if (! empty($validated['posted_message_id'])) {
            $report->update(['posted_message_id' => $validated['posted_message_id']]);
        }

        return ReportResource::make($report->load(['leader', 'players', 'challenge']));
    }

    public function reject(Request $request, Report $report): ReportResource
    {
        $validated = $request->validate([
            'reviewer_discord_id' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        // The report is discarded on reject; relations are already loaded.
        $report = $this->reports->reject($report, $validated['reviewer_discord_id'], $validated['note'] ?? null);

        return ReportResource::make($report);
    }

    /**
     * Download a remote (Discord CDN) image into public storage and return its path.
     */
    private function storeRemoteImage(string $url): string
    {
        $contents = Http::timeout(20)->get($url)->throw()->body();
        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'png';
        $path = 'reports/'.Str::uuid()->toString().'.'.$extension;

        Storage::disk('public')->put($path, $contents);

        return $path;
    }
}

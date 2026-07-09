<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Services\SeasonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function __construct(private readonly SeasonService $seasons) {}

    public function active(): JsonResponse
    {
        return response()->json(['data' => $this->transform($this->seasons->active())]);
    }

    public function startNew(): JsonResponse
    {
        return response()->json(['data' => $this->transform($this->seasons->startNew())], 201);
    }

    public function setCurrentNumber(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(['data' => $this->transform($this->seasons->setCurrentNumber($validated['number']))]);
    }

    /**
     * @return array<string, int>
     */
    private function transform(Season $season): array
    {
        return [
            'base' => $season->base,
            'last_number' => $season->lastNumber(),
            'next_number' => $season->next_number,
            'remaining' => max(0, $season->lastNumber() - $season->next_number + 1),
        ];
    }
}

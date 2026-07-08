<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Services\ChallengeService;
use App\Services\TierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChallengeController extends Controller
{
    public function __construct(
        private readonly ChallengeService $challenges,
        private readonly TierService $tiers,
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->challenges->listActive()->map(fn (Challenge $challenge) => [
            'id' => $challenge->id,
            'name' => $challenge->name,
            'tier' => $challenge->tier,
            'points' => $challenge->points,
        ])->values();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'tier' => ['required', 'string'],
        ]);

        $tier = $this->tiers->findByName($validated['tier']);

        if (! $tier) {
            throw ValidationException::withMessages([
                'tier' => "Unknown tier \"{$validated['tier']}\". Add it first with /tier-add.",
            ]);
        }

        $challenge = $this->challenges->create($validated['name'], $tier);

        return response()->json([
            'data' => [
                'id' => $challenge->id,
                'name' => $challenge->name,
                'tier' => $challenge->tier,
                'points' => $challenge->points,
            ],
        ], 201);
    }

    public function destroy(Challenge $challenge): JsonResponse
    {
        $this->challenges->remove($challenge);

        return response()->json(['ok' => true]);
    }
}

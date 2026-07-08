<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tier;
use App\Services\TierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TierController extends Controller
{
    public function __construct(private readonly TierService $tiers) {}

    public function index(): JsonResponse
    {
        $data = $this->tiers->listByPoints()->map(fn (Tier $tier) => [
            'id' => $tier->id,
            'name' => $tier->name,
            'points' => $tier->points,
        ])->values();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'points' => ['required', 'integer', 'min:0'],
        ]);

        $tier = $this->tiers->create($validated['name'], $validated['points']);

        return response()->json([
            'data' => ['id' => $tier->id, 'name' => $tier->name, 'points' => $tier->points],
        ], 201);
    }

    public function destroy(Tier $tier): JsonResponse
    {
        $this->tiers->remove($tier);

        return response()->json(['ok' => true]);
    }
}

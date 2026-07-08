<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ScoreboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoreboardController extends Controller
{
    public function index(Request $request, ScoreboardService $service): JsonResponse
    {
        $rows = $service->build(includeRetired: $request->boolean('include_retired', true));

        return response()->json(['data' => $rows->values()]);
    }
}

<?php

use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScoreboardController;
use App\Http\Controllers\Api\SeasonController;
use App\Http\Controllers\Api\TierController;
use Illuminate\Support\Facades\Route;

Route::middleware('bot.auth')->group(function () {
    Route::get('players', [PlayerController::class, 'index']);
    Route::post('players/sync', [PlayerController::class, 'sync']);
    Route::post('players/upsert', [PlayerController::class, 'upsert']);
    Route::post('players/deactivate', [PlayerController::class, 'deactivate']);

    Route::post('reports', [ReportController::class, 'store']);
    Route::get('reports/{report}', [ReportController::class, 'show']);
    Route::post('reports/{report}/approve', [ReportController::class, 'approve']);
    Route::post('reports/{report}/reject', [ReportController::class, 'reject']);

    Route::get('scoreboard', [ScoreboardController::class, 'index']);

    Route::get('challenges', [ChallengeController::class, 'index']);
    Route::post('challenges', [ChallengeController::class, 'store']);
    Route::delete('challenges/{challenge}', [ChallengeController::class, 'destroy']);

    Route::get('tiers', [TierController::class, 'index']);
    Route::post('tiers', [TierController::class, 'store']);
    Route::delete('tiers/{tier}', [TierController::class, 'destroy']);

    Route::post('games', [GameController::class, 'store']);
    Route::post('games/{game}/channel', [GameController::class, 'setChannel']);
    Route::get('games/by-channel', [GameController::class, 'byChannel']);

    Route::get('seasons/active', [SeasonController::class, 'active']);
    Route::post('seasons/new', [SeasonController::class, 'startNew']);
    Route::post('seasons/current-number', [SeasonController::class, 'setCurrentNumber']);
});

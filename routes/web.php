<?php

use App\Models\Report;
use App\Services\LeaderStatsService;
use App\Services\ScoreboardService;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

/*
| Render routes — HTML the bot's headless browser screenshots into PNGs.
| Protected by the same shared secret; the bot sends the Authorization header.
*/
Route::middleware('bot.auth')->prefix('render')->group(function () {
    Route::get('reports/{report}/card', function (Report $report) {
        return view('render.report-card', [
            'report' => $report->load(['leader', 'players', 'challenge']),
        ]);
    })->name('render.report-card');

    Route::get('scoreboard', function (ScoreboardService $service) {
        return view('render.scoreboard', [
            'rows' => $service->build()->all(),
        ]);
    })->name('render.scoreboard');

    Route::get('leaders', function (LeaderStatsService $service) {
        return view('render.leaders', [
            'rows' => $service->build()->all(),
        ]);
    })->name('render.leaders');
});

require __DIR__.'/settings.php';

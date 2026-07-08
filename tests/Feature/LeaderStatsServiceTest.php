<?php

use App\Models\Player;
use App\Models\Report;
use App\Services\LeaderStatsService;

it('counts approved games led per leader, ranked by games', function () {
    $busy = Player::factory()->create(['display_name' => 'Busy']);
    $quiet = Player::factory()->create(['display_name' => 'Quiet']);
    Player::factory()->create(['display_name' => 'NeverLed']);

    Report::factory()->count(3)->approved()->win()->for($busy, 'leader')->create();
    Report::factory()->approved()->loss()->for($busy, 'leader')->create();
    Report::factory()->approved()->win()->for($quiet, 'leader')->create();

    // Pending reports must not count.
    Report::factory()->win()->for($quiet, 'leader')->create();

    $rows = (new LeaderStatsService)->build();

    expect($rows)->toHaveCount(2);
    expect($rows->first())->toMatchArray([
        'name' => 'Busy',
        'games' => 4,
        'wins' => 3,
        'losses' => 1,
        'rank' => 1,
    ]);
    expect($rows->last())->toMatchArray([
        'name' => 'Quiet',
        'games' => 1,
        'rank' => 2,
    ]);
});

<?php

namespace Database\Factories;

use App\Enums\ReportResult;
use App\Enums\ReportStatus;
use App\Models\Player;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_number' => fake()->unique()->numberBetween(1, 999999),
            'game' => 'Asia',
            'day' => fake()->numberBetween(1, 60),
            'leader_id' => Player::factory(),
            'result' => fake()->randomElement(ReportResult::cases()),
            'status' => ReportStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => ReportStatus::Approved]);
    }

    public function win(): static
    {
        return $this->state(fn () => ['result' => ReportResult::Win]);
    }

    public function loss(): static
    {
        return $this->state(fn () => ['result' => ReportResult::Loss]);
    }
}

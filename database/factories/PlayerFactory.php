<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->userName();

        return [
            'discord_id' => (string) fake()->unique()->numerify('##################'),
            'username' => $name,
            'display_name' => fake()->name(),
            'avatar_url' => null,
            'is_retired' => false,
            'is_active' => true,
            'synced_at' => now(),
        ];
    }

    public function retired(): static
    {
        return $this->state(fn () => ['is_retired' => true]);
    }
}

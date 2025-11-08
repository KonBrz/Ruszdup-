<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flagged>
 */
class FlaggedFactory extends Factory
{
    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('-1 year', 'now');

        return [
            'user_id'=> null,
            'trip_id'=> null,
            'task_id'=> null,
            'reason'=> $this->faker->sentence(3),
            'is_closed'=> $this->faker->boolean(30),
            'decision'=> $this->faker->randomElement(['none','remove','up_to_standard']),
        ];
    }
}

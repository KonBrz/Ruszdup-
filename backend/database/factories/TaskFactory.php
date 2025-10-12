<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('-1 year', 'now');

        return [
            'title' => $this->faker->sentence(3),
            'priority' => $this->faker->randomElement(['niski', 'średni', 'wysoki']),
            'deadline' => $this->faker->optional(0.6)->dateTimeBetween('now', '+1 year'),
            'assigned_to' => null,
            'completed' => $this->faker->boolean(30),
            'created_at' => $createdAt,
            'updated_at' => $this->faker->dateTimeBetween($createdAt, 'now'),
        ];
    }
}

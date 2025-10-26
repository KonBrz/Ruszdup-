<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Najpierw utwórz użytkowników
        $users = User::factory(10)->create();
        User::factory()->admin()->create([
            'name' => 'Administrator',
            'email' => 'admin@ruszdupe.com',
        ]); // admin

        // Dla każdego użytkownika utwórz wycieczki
        foreach ($users as $user) {
            $trips = Trip::factory(5)->create([
                'user_id' => $user->id,
                'assigned_to' => rand(0, 100) <= 30 ? $users->random()->id : null, // ✅ 30% szans
                'destination' => fake()->city(),
                'start_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'end_date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            ]);

            // Dla każdej wycieczki utwórz zadania
            foreach ($trips as $trip) {
                Task::factory(rand(3, 8))->create([
                    'trip_id' => $trip->id,
                    'assigned_to' => rand(0, 100) <= 50 ? $users->random()->id : null, // ✅ 50% szans
                ]);
            }
        }

        // Użytkownik testowy
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
    }
}

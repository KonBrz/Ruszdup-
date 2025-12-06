<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use App\Models\Flagged;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $users = User::factory(10)->create();

        foreach ($users as $user) {

            $trips = Trip::factory(3)->create([
                'user_id' => $user->id,
                'destination' => fake()->city(),
                'start_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'end_date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            ]);

            foreach ($trips as $trip) {

                $trip->tripUsers()->attach(
                    $users->random(rand(1, 3))->pluck('id')->toArray()
                );

                $tasks = Task::factory(rand(3, 8))->create([
                    'trip_id' => $trip->id,
                    'user_id' => $trip->user_id,
                ]);

                foreach ($tasks as $task) {
                    $task->taskUsers()->attach(
                        $users->random(rand(1, 2))->pluck('id')->toArray()
                    );
                }

                Flagged::factory(rand(5, 10))->create([
                    'user_id' => rand(0, 1) ? $users->random()->id : null,
                    'trip_id' => rand(0, 1) ? $trips->random()->id : null,
                    'task_id' => rand(0, 1) ? $tasks->random()->id : null,
                ]);
            }
        }



        // Użytkownik testowy
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        User::factory()->admin()->create([
            'name' => 'Administrator',
            'email' => 'admin@ruszdupe.com',
        ]); // admin

        $trip = Trip::factory()->create([
            'user_id' => 11,
            'destination' => 'Warszawa',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $trip->tripUsers()->attach(
            User::inRandomOrder()->take(3)->pluck('id')->toArray()
        );
// Stwórz 5 tasków w tym tripie
        $tasks = Task::factory(5)->create([
            'trip_id' => $trip->id,
            'user_id' => $trip->user_id,
        ]);

        foreach ($tasks as $task) {
            // Zawsze dodaj użytkownika 11 do taska
            $userIds = [11];

            // Dodaj dodatkowo 1-2 losowych użytkowników
            $randomUsers = User::where('id', '!=', 11)->inRandomOrder()->take(rand(1, 2))->pluck('id')->toArray();
            $userIds = array_merge($userIds, $randomUsers);

            $task->taskUsers()->attach($userIds);
        }

    }
}

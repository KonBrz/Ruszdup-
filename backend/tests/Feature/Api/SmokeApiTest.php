<?php

namespace Tests\Feature\Api;

use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SmokeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_smoke_api_endpoints(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'destination' => 'Gdansk',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
        ]);
        $trip->tripUsers()->attach($user->id);

        $task = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);
        $task->taskUsers()->attach($user->id, ['completed' => 0, 'ignored' => 0]);

        Sanctum::actingAs($user);

        $this->getJson('/api/trips')->assertStatus(200);
        $this->getJson('/api/tasks')->assertStatus(200);
        $this->postJson('/api/flagged', ['reason' => 'Smoke test', 'trip_id' => $trip->id])->assertStatus(201);
        $this->postJson('/api/ai-chat', ['prompt' => 'Test'])->assertStatus(200);
    }
}

<?php

namespace Tests\Feature\Api\Tasks;

use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeTrip(User $owner): Trip
    {
        $trip = Trip::factory()->create([
            'user_id' => $owner->id,
            'destination' => 'Gdansk',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
        ]);
        $trip->tripUsers()->attach($owner->id);

        return $trip;
    }

    public function test_get_tasks_requires_authentication(): void
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401);
    }

    public function test_get_tasks_returns_only_tasks_where_user_is_in_pivot(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $trip = $this->makeTrip($otherUser);

        $taskIn = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $otherUser->id,
        ]);
        $taskIn->taskUsers()->attach($user->id, ['completed' => 1, 'ignored' => 0]);

        $taskOut = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $otherUser->id,
        ]);
        $taskOut->taskUsers()->attach($otherUser->id, ['completed' => 0, 'ignored' => 0]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $taskIn->id])
            ->assertJsonMissing(['id' => $taskOut->id])
            ->assertJsonStructure([
                '*' => [
                    'task_users' => [
                        '*' => [
                            'pivot' => ['completed', 'ignored', 'task_id', 'user_id'],
                        ],
                    ],
                ],
            ]);
    }

    public function test_get_trip_tasks_requires_authentication(): void
    {
        $trip = $this->makeTrip(User::factory()->create());

        $response = $this->getJson("/api/trips/{$trip->id}/tasks");

        $response->assertStatus(401);
    }

    public function test_get_trip_tasks_returns_tasks_created_by_user_with_relations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $tripA = $this->makeTrip($user);
        $tripB = $this->makeTrip($user);

        $taskA = Task::factory()->create(['trip_id' => $tripA->id, 'user_id' => $user->id]);
        $taskB = Task::factory()->create(['trip_id' => $tripB->id, 'user_id' => $user->id]);
        $taskOther = Task::factory()->create(['trip_id' => $tripA->id, 'user_id' => $otherUser->id]);

        $tripA->tripUsers()->attach($user->id);

        $taskA->taskUsers()->attach($user->id);
        $taskB->taskUsers()->attach($user->id);
        $taskOther->taskUsers()->attach($otherUser->id);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/trips/{$tripA->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $taskA->id])
            ->assertJsonFragment(['id' => $taskOther->id])
            ->assertJsonStructure([
                '*' => [
                    'trip',
                    'task_users',
                ],
            ]);

        $taskIds = collect($response->json())->pluck('id')->all();
        $this->assertNotContains($taskB->id, $taskIds);
    }

    public function test_get_trip_tasks_returns_403_when_user_not_in_trip(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Task::factory()->create(['trip_id' => $trip->id, 'user_id' => $owner->id]);

        Sanctum::actingAs($outsider);

        $response = $this->getJson("/api/trips/{$trip->id}/tasks");

        $response->assertStatus(403);
    }

    public function test_store_task_requires_authentication(): void
    {
        $response = $this->postJson('/api/tasks', []);

        $response->assertStatus(401);
    }

    public function test_store_task_validates_input(): void
    {
        $user = User::factory()->create();
        $trip = $this->makeTrip($user);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'trip_id']);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test',
            'priority' => 'high',
            'trip_id' => $trip->id,
            'user_ids' => ['999999'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority', 'user_ids.0']);
    }

    public function test_store_task_creates_task_and_syncs_users(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();
        $trip = $this->makeTrip($user);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Pakowanie',
            'priority' => 'niski',
            'deadline' => '2025-02-10',
            'trip_id' => $trip->id,
            'user_ids' => [$assignee->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Pakowanie']);

        $taskId = $response->json('id');

        $this->assertDatabaseHas('tasks', [
            'id' => $taskId,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('task_user', [
            'task_id' => $taskId,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_show_task_requires_authentication(): void
    {
        $task = Task::factory()->create([
            'trip_id' => $this->makeTrip(User::factory()->create())->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(401);
    }

    public function test_show_task_returns_404_for_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tasks/999999');

        $response->assertStatus(404);
    }

    public function test_show_task_includes_relations_and_flag(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach([$owner->id, $member->id]);

        $task = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
        ]);
        $task->taskUsers()->attach([$owner->id, $member->id]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'trip' => ['trip_users'],
                'task_users',
                'can_edit',
            ])
            ->assertJsonPath('can_edit', true);
    }

    public function test_update_task_requires_authentication(): void
    {
        $task = Task::factory()->create([
            'trip_id' => $this->makeTrip(User::factory()->create())->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", ['title' => 'Nowy']);

        $response->assertStatus(401);
    }

    public function test_update_task_validates_input(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'trip_id' => $this->makeTrip($user)->id,
            'user_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'priority' => 'high',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_update_task_updates_task_and_pivot_and_users(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
        ]);
        $task->taskUsers()->attach([
            $owner->id => ['completed' => 0, 'ignored' => 0],
            $otherUser->id => ['completed' => 1, 'ignored' => 0],
        ]);

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Zmienione',
            'priority' => 'średni',
            'completed' => true,
            'ignored' => false,
            'user_ids' => [$owner->id, $otherUser->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Zmienione']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Zmienione',
            'priority' => 'średni',
        ]);
        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'completed' => 1,
            'ignored' => 0,
        ]);
        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $otherUser->id,
            'completed' => 1,
            'ignored' => 0,
        ]);
    }

    public function test_update_completed_and_ignored_requires_authentication(): void
    {
        $task = Task::factory()->create([
            'trip_id' => $this->makeTrip(User::factory()->create())->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->putJson("/api/tasks/update/{$task->id}", [
            'completed' => true,
            'ignored' => false,
        ]);

        $response->assertStatus(401);
    }

    public function test_update_completed_and_ignored_validates_input(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'trip_id' => $this->makeTrip($user)->id,
            'user_id' => $user->id,
        ]);
        $task->taskUsers()->attach($user->id, ['completed' => 0, 'ignored' => 0]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/tasks/update/{$task->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['completed', 'ignored']);

        $response = $this->putJson("/api/tasks/update/{$task->id}", [
            'completed' => 'yes',
            'ignored' => 'no',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['completed', 'ignored']);
    }

    public function test_update_completed_and_ignored_updates_only_current_user_pivot(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $trip = $this->makeTrip($user);
        $task = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);
        $task->taskUsers()->attach([
            $user->id => ['completed' => 0, 'ignored' => 0],
            $otherUser->id => ['completed' => 1, 'ignored' => 0],
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/tasks/update/{$task->id}", [
            'completed' => true,
            'ignored' => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'completed' => 1,
            'ignored' => 1,
        ]);
        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $otherUser->id,
            'completed' => 1,
            'ignored' => 0,
        ]);
    }

    public function test_update_completed_and_ignored_when_user_not_in_pivot(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
        ]);
        $task->taskUsers()->attach($owner->id, ['completed' => 0, 'ignored' => 0]);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/tasks/update/{$task->id}", [
            'completed' => true,
            'ignored' => false,
        ]);

        // User not assigned to task should get 403
        $response->assertStatus(403);

        $this->assertDatabaseMissing('task_user', [
            'task_id' => $task->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_delete_task_requires_authentication(): void
    {
        $task = Task::factory()->create([
            'trip_id' => $this->makeTrip(User::factory()->create())->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(401);
    }

    public function test_delete_task_returns_404_for_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/tasks/999999');

        $response->assertStatus(404);
    }

    public function test_delete_task_deletes_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'trip_id' => $this->makeTrip($user)->id,
            'user_id' => $user->id,
        ]);
        $task->taskUsers()->attach($user->id, ['completed' => 0, 'ignored' => 0]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}

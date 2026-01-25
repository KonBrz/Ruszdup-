<?php

namespace Tests\Feature\Api\Tasks;

use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Authorization tests for Task API endpoints.
 *
 * Tests TaskPolicy enforcement: view (trip member), update/delete (creator or trip owner).
 * Also tests task creation membership check and pivot update assignment check.
 */
#[Group('authz')]
class TaskAuthorizationTest extends TestCase
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

    private function makeTask(Trip $trip, User $creator): Task
    {
        return Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $creator->id,
        ]);
    }

    // =========================================================================
    // SHOW - Authorization
    // =========================================================================

    /**
     * TODO: requires authorization layer (TaskPolicy@view)
     * Outsider (not in trip) should not be able to view task.
     */
    public function test_outsider_cannot_show_task(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = $this->makeTask($trip, $owner);

        Sanctum::actingAs($outsider);

        $response = $this->getJson("/api/tasks/{$task->id}");

        // EXPECTED: 403 Forbidden (outsider not in trip)
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);
    }

    /**
     * Member of trip should be able to view task in that trip.
     */
    public function test_member_can_show_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);
        $task = $this->makeTask($trip, $owner);

        Sanctum::actingAs($member);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $task->id);
    }

    /**
     * Task show returns correct can_edit flag for creator vs non-creator.
     */
    public function test_show_task_returns_can_edit_true_for_creator(): void
    {
        $creator = User::factory()->create();
        $trip = $this->makeTrip($creator);
        $task = $this->makeTask($trip, $creator);

        Sanctum::actingAs($creator);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('can_edit', true);
    }

    /**
     * Task show returns can_edit=false for non-creator member.
     */
    public function test_show_task_returns_can_edit_false_for_non_creator(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);
        $task = $this->makeTask($trip, $owner);

        Sanctum::actingAs($member);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('can_edit', false);
    }

    // =========================================================================
    // STORE - Authorization
    // =========================================================================

    /**
     * TODO: requires authorization layer (TaskPolicy@create)
     * Outsider should not be able to create task for trip they don't belong to.
     */
    public function test_outsider_cannot_create_task_for_trip(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($outsider);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Hacked Task',
            'trip_id' => $trip->id,
        ]);

        // EXPECTED: 403 Forbidden (outsider not in trip)
        // CURRENT: 201 Created (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseMissing('tasks', [
            'trip_id' => $trip->id,
            'title' => 'Hacked Task',
        ]);
    }

    /**
     * Member should be able to create task for trip they belong to.
     */
    public function test_member_can_create_task_for_trip(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);

        Sanctum::actingAs($member);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Member Task',
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Member Task']);

        $this->assertDatabaseHas('tasks', [
            'trip_id' => $trip->id,
            'user_id' => $member->id,
            'title' => 'Member Task',
        ]);
    }

    /**
     * Creating task without user_ids should succeed (no assignees).
     */
    public function test_create_task_without_user_ids(): void
    {
        $user = User::factory()->create();
        $trip = $this->makeTrip($user);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'title' => 'No Assignees',
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(201);

        $taskId = $response->json('id');
        $this->assertDatabaseMissing('task_user', ['task_id' => $taskId]);
    }

    // =========================================================================
    // UPDATE - Authorization
    // =========================================================================

    /**
     * TODO: requires authorization layer (TaskPolicy@update)
     * Outsider should not be able to update task.
     */
    public function test_outsider_cannot_update_task(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = $this->makeTask($trip, $owner);

        Sanctum::actingAs($outsider);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Hacked Title',
        ]);

        // EXPECTED: 403 Forbidden
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
            'title' => 'Hacked Title',
        ]);
    }

    /**
     * TODO: requires authorization layer (TaskPolicy@update)
     * Member (not creator, not trip owner) should not be able to update task.
     */
    public function test_non_creator_member_cannot_update_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);
        $task = $this->makeTask($trip, $owner);

        Sanctum::actingAs($member);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Member Edit',
        ]);

        // EXPECTED: 403 Forbidden (only creator or trip owner can update)
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);
    }

    /**
     * Creator should be able to update their own task.
     */
    public function test_creator_can_update_task(): void
    {
        $creator = User::factory()->create();
        $trip = $this->makeTrip($creator);
        $task = $this->makeTask($trip, $creator);

        Sanctum::actingAs($creator);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Creator Edit',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Creator Edit']);
    }

    /**
     * Trip owner should be able to update any task in their trip.
     */
    public function test_trip_owner_can_update_member_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);
        $task = $this->makeTask($trip, $member);

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Owner Edit',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Owner Edit']);
    }

    /**
     * Update task with user_ids syncs pivot correctly.
     */
    public function test_update_task_with_user_ids_syncs_pivot(): void
    {
        $owner = User::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = $this->makeTask($trip, $owner);

        // Initially assign user1 and user2
        $task->taskUsers()->attach([
            $user1->id => ['completed' => 0, 'ignored' => 0],
            $user2->id => ['completed' => 1, 'ignored' => 0],
        ]);

        Sanctum::actingAs($owner);

        // Update to user2 and user3 (remove user1, keep user2, add user3)
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'user_ids' => [$user2->id, $user3->id],
        ]);

        $response->assertStatus(200);

        // user1 should be removed
        $this->assertDatabaseMissing('task_user', [
            'task_id' => $task->id,
            'user_id' => $user1->id,
        ]);

        // user2 should remain (sync replaces pivot data)
        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $user2->id,
        ]);

        // user3 should be added
        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $user3->id,
        ]);
    }

    /**
     * Update task with empty user_ids clears all assignees.
     */
    public function test_update_task_with_empty_user_ids_clears_assignees(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = $this->makeTask($trip, $owner);
        $task->taskUsers()->attach($assignee->id, ['completed' => 0, 'ignored' => 0]);

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'user_ids' => [],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('task_user', [
            'task_id' => $task->id,
        ]);
    }

    // =========================================================================
    // DELETE - Authorization
    // =========================================================================

    /**
     * TODO: requires authorization layer (TaskPolicy@delete)
     * Outsider should not be able to delete task.
     */
    public function test_outsider_cannot_delete_task(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = $this->makeTask($trip, $owner);

        Sanctum::actingAs($outsider);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        // EXPECTED: 403 Forbidden
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    /**
     * TODO: requires authorization layer (TaskPolicy@delete)
     * Member (not creator, not trip owner) should not be able to delete task.
     */
    public function test_non_creator_member_cannot_delete_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);
        $task = $this->makeTask($trip, $owner);

        Sanctum::actingAs($member);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        // EXPECTED: 403 Forbidden
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    /**
     * Creator should be able to delete their own task.
     */
    public function test_creator_can_delete_task(): void
    {
        $creator = User::factory()->create();
        $trip = $this->makeTrip($creator);
        $task = $this->makeTask($trip, $creator);

        Sanctum::actingAs($creator);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /**
     * Trip owner should be able to delete any task in their trip.
     */
    public function test_trip_owner_can_delete_member_task(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);
        $task = $this->makeTask($trip, $member);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    // =========================================================================
    // UPDATE COMPLETED/IGNORED - Pivot behavior
    // =========================================================================

    /**
     * PUT /api/tasks/update/{id} when user not assigned to task returns 403.
     */
    public function test_update_pivot_when_user_not_assigned_returns_403(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = $this->makeTask($trip, $owner);
        $task->taskUsers()->attach($owner->id, ['completed' => 0, 'ignored' => 0]);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/tasks/update/{$task->id}", [
            'completed' => true,
            'ignored' => true,
        ]);

        $response->assertStatus(403);

        // otherUser should NOT be in pivot
        $this->assertDatabaseMissing('task_user', [
            'task_id' => $task->id,
            'user_id' => $otherUser->id,
        ]);

        // owner's pivot unchanged
        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'completed' => 0,
            'ignored' => 0,
        ]);
    }

    /**
     * Assigned user can update their own pivot completed/ignored.
     */
    public function test_assigned_user_can_update_own_pivot(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = $this->makeTask($trip, $owner);
        $task->taskUsers()->attach([
            $owner->id => ['completed' => 0, 'ignored' => 0],
            $assignee->id => ['completed' => 0, 'ignored' => 0],
        ]);

        Sanctum::actingAs($assignee);

        $response = $this->putJson("/api/tasks/update/{$task->id}", [
            'completed' => true,
            'ignored' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'completed' => 1,
            'ignored' => 0,
        ]);

        // Owner's pivot should be unchanged
        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'completed' => 0,
            'ignored' => 0,
        ]);
    }

    // =========================================================================
    // Policy Discovery
    // =========================================================================

    /**
     * Verify that TaskPolicy is correctly registered with Gate.
     */
    public function test_task_policy_is_registered(): void
    {
        $policy = \Illuminate\Support\Facades\Gate::getPolicyFor(Task::class);

        $this->assertNotNull($policy);
        $this->assertInstanceOf(\App\Policies\TaskPolicy::class, $policy);
    }
}

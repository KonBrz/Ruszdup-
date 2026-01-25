<?php

namespace Tests\Feature\Api;

use App\Models\Flagged;
use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * API tests for Flagged (reporting) endpoints.
 *
 * Tests validation, authorization (trip/task membership), and CRUD operations.
 */
#[Group('authz')]
class FlaggedApiTest extends TestCase
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

    // =========================================================================
    // Authentication
    // =========================================================================

    public function test_store_flag_requires_authentication(): void
    {
        $response = $this->postJson('/api/flagged', []);

        $response->assertStatus(401);
    }

    // =========================================================================
    // Validation
    // =========================================================================

    public function test_store_flag_validates_reason(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/flagged', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_store_flag_validates_related_ids(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'Zgloszenie',
            'user_id' => 999999,
            'trip_id' => 999999,
            'task_id' => 999999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'trip_id', 'task_id']);
    }

    /**
     * Flag with reason and user target succeeds.
     */
    public function test_store_flag_with_reason_and_user_target_succeeds(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'General complaint',
            'user_id' => $targetUser->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('flagged', [
            'reason' => 'General complaint',
            'user_id' => $targetUser->id,
            'trip_id' => null,
            'task_id' => null,
        ]);
    }

    /**
     * TODO: requires validation enhancement
     * Flag without any target (user/trip/task) should return 422.
     * At least one target should be required.
     */
    public function test_store_flag_without_any_target_should_return_422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'No target specified',
        ]);

        // EXPECTED: 422 Unprocessable (at least one target required)
        // CURRENT: 201 Created (no validation for at least one target)
        $response->assertStatus(422);
    }

    // =========================================================================
    // Successful flag creation
    // =========================================================================

    public function test_store_flag_creates_record(): void
    {
        $owner = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
        ]);
        $flaggedUser = User::factory()->create();

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'Nieodpowiednie zachowanie',
            'user_id' => $flaggedUser->id,
            'trip_id' => $trip->id,
            'task_id' => $task->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['reason' => 'Nieodpowiednie zachowanie']);

        $this->assertDatabaseHas('flagged', [
            'user_id' => $flaggedUser->id,
            'trip_id' => $trip->id,
            'task_id' => $task->id,
            'reason' => 'Nieodpowiednie zachowanie',
        ]);
    }

    /**
     * Member can flag trip they belong to.
     */
    public function test_member_can_flag_trip_they_belong_to(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);

        Sanctum::actingAs($member);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'Problem with trip',
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('flagged', [
            'trip_id' => $trip->id,
            'reason' => 'Problem with trip',
        ]);
    }

    /**
     * Member can flag task in trip they belong to.
     */
    public function test_member_can_flag_task_in_their_trip(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);
        $task = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($member);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'Inappropriate task',
            'task_id' => $task->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('flagged', [
            'task_id' => $task->id,
            'reason' => 'Inappropriate task',
        ]);
    }

    /**
     * User can flag another user (e.g., trip member).
     */
    public function test_user_can_flag_another_user(): void
    {
        $reporter = User::factory()->create();
        $flaggedUser = User::factory()->create();

        Sanctum::actingAs($reporter);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'Spam behavior',
            'user_id' => $flaggedUser->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('flagged', [
            'user_id' => $flaggedUser->id,
            'reason' => 'Spam behavior',
        ]);
    }

    /**
     * Flag with only trip_id (no user/task).
     */
    public function test_store_flag_only_trip(): void
    {
        $owner = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'Trip issue',
            'trip_id' => $trip->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('flagged', [
            'trip_id' => $trip->id,
            'user_id' => null,
            'task_id' => null,
            'reason' => 'Trip issue',
        ]);
    }

    /**
     * Flag with only task_id (no user/trip).
     */
    public function test_store_flag_only_task(): void
    {
        $owner = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'Task issue',
            'task_id' => $task->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('flagged', [
            'task_id' => $task->id,
            'user_id' => null,
            'trip_id' => null,
            'reason' => 'Task issue',
        ]);
    }

    /**
     * Flag with only user_id (no trip/task).
     */
    public function test_store_flag_only_user(): void
    {
        $reporter = User::factory()->create();
        $flaggedUser = User::factory()->create();

        Sanctum::actingAs($reporter);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'User issue',
            'user_id' => $flaggedUser->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('flagged', [
            'user_id' => $flaggedUser->id,
            'trip_id' => null,
            'task_id' => null,
            'reason' => 'User issue',
        ]);
    }

    // =========================================================================
    // Authorization (TODO: requires authorization layer)
    // =========================================================================

    /**
     * TODO: requires authorization layer
     * Outsider should not be able to flag trip they don't belong to.
     */
    public function test_outsider_cannot_flag_trip_they_dont_belong_to(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($outsider);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'Hacker attempt',
            'trip_id' => $trip->id,
        ]);

        // EXPECTED: 403 Forbidden (outsider not in trip)
        // CURRENT: 201 Created (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseMissing('flagged', [
            'trip_id' => $trip->id,
            'reason' => 'Hacker attempt',
        ]);
    }

    /**
     * TODO: requires authorization layer
     * Outsider should not be able to flag task from trip they don't belong to.
     */
    public function test_outsider_cannot_flag_task_from_foreign_trip(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $task = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($outsider);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'Hacker attempt',
            'task_id' => $task->id,
        ]);

        // EXPECTED: 403 Forbidden (outsider not in trip)
        // CURRENT: 201 Created (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseMissing('flagged', [
            'task_id' => $task->id,
            'reason' => 'Hacker attempt',
        ]);
    }

    // =========================================================================
    // Edge cases
    // =========================================================================

    /**
     * Flag with maximum length reason (255 chars).
     */
    public function test_store_flag_with_max_length_reason(): void
    {
        $user = User::factory()->create();
        $flaggedUser = User::factory()->create();
        $maxReason = str_repeat('a', 255);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/flagged', [
            'reason' => $maxReason,
            'user_id' => $flaggedUser->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('flagged', [
            'user_id' => $flaggedUser->id,
            'reason' => $maxReason,
        ]);
    }

    /**
     * Flag with reason exceeding max length returns 422.
     */
    public function test_store_flag_with_too_long_reason_returns_422(): void
    {
        $user = User::factory()->create();
        $flaggedUser = User::factory()->create();
        $tooLongReason = str_repeat('a', 256);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/flagged', [
            'reason' => $tooLongReason,
            'user_id' => $flaggedUser->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /**
     * User can flag themselves (edge case, should probably be blocked).
     */
    public function test_user_can_flag_themselves(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/flagged', [
            'reason' => 'Self-report',
            'user_id' => $user->id,
        ]);

        // Current: 201 Created (no validation against self-flagging)
        // This might be desired or not depending on business rules
        $response->assertStatus(201);
    }

    /**
     * Multiple flags for same target should be allowed (no duplicate check).
     */
    public function test_multiple_flags_for_same_target_allowed(): void
    {
        $reporter = User::factory()->create();
        $flaggedUser = User::factory()->create();

        Sanctum::actingAs($reporter);

        // First flag
        $this->postJson('/api/flagged', [
            'reason' => 'First report',
            'user_id' => $flaggedUser->id,
        ])->assertStatus(201);

        // Second flag (same target)
        $this->postJson('/api/flagged', [
            'reason' => 'Second report',
            'user_id' => $flaggedUser->id,
        ])->assertStatus(201);

        $this->assertDatabaseCount('flagged', 2);
    }
}

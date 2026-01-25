<?php

namespace Tests\Feature\Api\Trips;

use App\Models\Task;
use App\Models\Trip;
use App\Models\TripInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Authorization tests for Trip API endpoints.
 *
 * Tests TripPolicy enforcement: view (member), update/delete/invite/removeUser (owner).
 */
#[Group('authz')]
class TripAuthorizationTest extends TestCase
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
    // SHOW - Authorization
    // =========================================================================

    /**
     * TODO: requires authorization layer (TripPolicy@view)
     * Outsider should not be able to view trip details they don't belong to.
     */
    public function test_outsider_cannot_show_trip(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($outsider);

        $response = $this->getJson("/api/trips/{$trip->id}");

        // EXPECTED: 403 Forbidden (outsider has no access)
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);
    }

    /**
     * Member (not owner) should be able to view trip they belong to.
     */
    public function test_member_can_show_trip(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);

        Sanctum::actingAs($member);

        $response = $this->getJson("/api/trips/{$trip->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $trip->id);
    }

    // =========================================================================
    // UPDATE - Authorization
    // =========================================================================

    /**
     * TODO: requires authorization layer (TripPolicy@update)
     * Outsider should not be able to update trip they don't own.
     */
    public function test_outsider_cannot_update_trip(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($outsider);

        $response = $this->putJson("/api/trips/{$trip->id}", [
            'title' => 'Hacked Title',
        ]);

        // EXPECTED: 403 Forbidden
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseMissing('trips', [
            'id' => $trip->id,
            'title' => 'Hacked Title',
        ]);
    }

    /**
     * TODO: requires authorization layer (TripPolicy@update)
     * Member (not owner) should not be able to update trip.
     */
    public function test_member_cannot_update_trip(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);

        Sanctum::actingAs($member);

        $response = $this->putJson("/api/trips/{$trip->id}", [
            'title' => 'Member Edit',
        ]);

        // EXPECTED: 403 Forbidden (only owner can update)
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);
    }

    /**
     * Owner should be able to update their own trip.
     */
    public function test_owner_can_update_trip(): void
    {
        $owner = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/trips/{$trip->id}", [
            'title' => 'Owner Edit',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Owner Edit']);
    }

    public function test_update_trip_returns_404_for_nonexistent(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/trips/999999', [
            'title' => 'Test',
        ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // DELETE - Authorization
    // =========================================================================

    /**
     * TODO: requires authorization layer (TripPolicy@delete)
     * Outsider should not be able to delete trip they don't own.
     */
    public function test_outsider_cannot_delete_trip(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($outsider);

        $response = $this->deleteJson("/api/trips/{$trip->id}");

        // EXPECTED: 403 Forbidden
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseHas('trips', ['id' => $trip->id]);
    }

    /**
     * TODO: requires authorization layer (TripPolicy@delete)
     * Member (not owner) should not be able to delete trip.
     */
    public function test_member_cannot_delete_trip(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);

        Sanctum::actingAs($member);

        $response = $this->deleteJson("/api/trips/{$trip->id}");

        // EXPECTED: 403 Forbidden
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseHas('trips', ['id' => $trip->id]);
    }

    /**
     * Owner should be able to delete their own trip.
     */
    public function test_owner_can_delete_trip(): void
    {
        $owner = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/trips/{$trip->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
    }

    // =========================================================================
    // DELETE - Cascade behavior
    // =========================================================================

    /**
     * Deleting trip should cascade delete all related data.
     */
    public function test_delete_trip_cascades_tasks_pivots_and_invitations(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);

        // Create tasks with pivot data
        $task1 = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
        ]);
        $task1->taskUsers()->attach([
            $owner->id => ['completed' => 0, 'ignored' => 0],
            $member->id => ['completed' => 1, 'ignored' => 0],
        ]);

        $task2 = Task::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $member->id,
        ]);
        $task2->taskUsers()->attach($member->id, ['completed' => 0, 'ignored' => 1]);

        // Create invitations
        $invitation = TripInvitation::factory()->create(['trip_id' => $trip->id]);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/trips/{$trip->id}");

        $response->assertStatus(200);

        // Trip deleted
        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);

        // Tasks deleted
        $this->assertDatabaseMissing('tasks', ['id' => $task1->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task2->id]);

        // Trip-user pivot deleted
        $this->assertDatabaseMissing('trip_user', ['trip_id' => $trip->id]);

        // Task-user pivot deleted
        $this->assertDatabaseMissing('task_user', ['task_id' => $task1->id]);
        $this->assertDatabaseMissing('task_user', ['task_id' => $task2->id]);

        // Invitations deleted
        $this->assertDatabaseMissing('trip_invitations', ['id' => $invitation->id]);
    }

    // =========================================================================
    // INVITE - Authorization
    // =========================================================================

    /**
     * TODO: requires authorization layer (TripPolicy@invite)
     * Outsider should not be able to generate invite link.
     */
    public function test_outsider_cannot_generate_invite_link(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($outsider);

        $response = $this->postJson("/api/trips/{$trip->id}/invite");

        // EXPECTED: 403 Forbidden
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);
    }

    /**
     * TODO: requires authorization layer (TripPolicy@invite)
     * Member (not owner) should not be able to generate invite link.
     */
    public function test_member_cannot_generate_invite_link(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);

        Sanctum::actingAs($member);

        $response = $this->postJson("/api/trips/{$trip->id}/invite");

        // EXPECTED: 403 Forbidden (only owner can invite)
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);
    }

    /**
     * Owner should be able to generate invite link.
     */
    public function test_owner_can_generate_invite_link(): void
    {
        $owner = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/trips/{$trip->id}/invite");

        $response->assertStatus(200)
            ->assertJsonStructure(['link']);
    }

    public function test_invite_returns_404_for_nonexistent_trip(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/trips/999999/invite');

        $response->assertStatus(404);
    }

    // =========================================================================
    // ACCEPT INVITATION - Edge cases
    // =========================================================================

    /**
     * Accepting invitation twice should be idempotent (no duplicate pivot).
     */
    public function test_accept_invitation_is_idempotent(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $invitation = TripInvitation::factory()->create(['trip_id' => $trip->id]);

        Sanctum::actingAs($member);

        // First accept
        $response1 = $this->postJson('/api/trip-invite/accept', [
            'token' => $invitation->token,
        ]);
        $response1->assertStatus(200);

        // Second accept (same token)
        $response2 = $this->postJson('/api/trip-invite/accept', [
            'token' => $invitation->token,
        ]);
        $response2->assertStatus(200);

        // Should have exactly one pivot record (not duplicated)
        $this->assertDatabaseCount('trip_user', 2); // owner + member

        $this->assertEquals(
            1,
            $trip->tripUsers()->where('user_id', $member->id)->count()
        );
    }

    /**
     * Accept invitation with invalid token returns 404.
     */
    public function test_accept_invitation_with_invalid_token_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/trip-invite/accept', [
            'token' => 'invalid-uuid-token',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Accept invitation with empty token returns 404.
     */
    public function test_accept_invitation_with_empty_token_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/trip-invite/accept', [
            'token' => '',
        ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // DELETE USER - Authorization
    // =========================================================================

    /**
     * TODO: requires authorization layer (TripPolicy@removeUser)
     * Outsider should not be able to remove users from trip.
     */
    public function test_outsider_cannot_delete_user_from_trip(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);

        Sanctum::actingAs($outsider);

        $response = $this->deleteJson("/api/trips/{$trip->id}/deleteuser/{$member->id}");

        // EXPECTED: 403 Forbidden
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $member->id,
        ]);
    }

    /**
     * TODO: requires authorization layer (TripPolicy@removeUser)
     * Member (not owner) should not be able to remove other users.
     */
    public function test_member_cannot_delete_other_user_from_trip(): void
    {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach([$member1->id, $member2->id]);

        Sanctum::actingAs($member1);

        $response = $this->deleteJson("/api/trips/{$trip->id}/deleteuser/{$member2->id}");

        // EXPECTED: 403 Forbidden (only owner can remove users)
        // CURRENT: 200 OK (no authorization check)
        $response->assertStatus(403);

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $member2->id,
        ]);
    }

    /**
     * Owner should be able to remove members from trip.
     */
    public function test_owner_can_delete_user_from_trip(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = $this->makeTrip($owner);
        $trip->tripUsers()->attach($member->id);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/trips/{$trip->id}/deleteuser/{$member->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_delete_user_from_nonexistent_trip_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/trips/999999/deleteuser/1');

        $response->assertStatus(404);
    }

    /**
     * Deleting non-member user from trip should succeed silently (detach no-op).
     */
    public function test_delete_nonmember_user_from_trip_succeeds(): void
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $trip = $this->makeTrip($owner);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/trips/{$trip->id}/deleteuser/{$nonMember->id}");

        $response->assertStatus(200);
    }

    // =========================================================================
    // Policy Discovery
    // =========================================================================

    /**
     * Verify that TripPolicy is correctly registered with Gate.
     */
    public function test_trip_policy_is_registered(): void
    {
        $policy = \Illuminate\Support\Facades\Gate::getPolicyFor(Trip::class);

        $this->assertNotNull($policy);
        $this->assertInstanceOf(\App\Policies\TripPolicy::class, $policy);
    }
}

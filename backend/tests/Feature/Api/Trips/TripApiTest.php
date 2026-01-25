<?php

namespace Tests\Feature\Api\Trips;

use App\Models\Task;
use App\Models\Trip;
use App\Models\TripInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TripApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_trips_requires_authentication(): void
    {
        $response = $this->getJson('/api/trips');

        $response->assertStatus(401);
    }

    public function test_get_trips_returns_only_trips_for_user_and_includes_pivot(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $tripIn = Trip::factory()->for($owner, 'user')->create();
        $tripOut = Trip::factory()->for($owner, 'user')->create();

        $tripIn->tripUsers()->attach($user->id);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/trips');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $tripIn->id])
            ->assertJsonMissing(['id' => $tripOut->id])
            ->assertJsonStructure([
                '*' => [
                    'pivot' => ['user_id', 'trip_id'],
                ],
            ]);
    }

    public function test_post_trips_requires_authentication(): void
    {
        $response = $this->postJson('/api/trips', []);

        $response->assertStatus(401);
    }

    public function test_post_trips_validates_input(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/trips', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'destination', 'start_date', 'end_date']);

        $response = $this->postJson('/api/trips', [
            'title' => 'Wyjazd',
            'destination' => 'Gdansk',
            'start_date' => '2025-02-10',
            'end_date' => '2025-02-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_post_trips_creates_trip_and_attaches_creator(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Wyjazd testowy',
            'destination' => 'Krakow',
            'description' => 'Opis',
            'start_date' => '2025-03-01',
            'end_date' => '2025-03-05',
        ];

        $response = $this->postJson('/api/trips', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Wyjazd testowy']);

        $this->assertDatabaseHas('trips', [
            'title' => 'Wyjazd testowy',
            'destination' => 'Krakow',
            'user_id' => $user->id,
        ]);

        $tripId = $response->json('id');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $tripId,
            'user_id' => $user->id,
        ]);
    }

    public function test_show_trip_requires_authentication(): void
    {
        $trip = Trip::factory()->create();

        $response = $this->getJson("/api/trips/{$trip->id}");

        $response->assertStatus(401);
    }

    public function test_show_trip_returns_404_for_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/trips/999999');

        $response->assertStatus(404);
    }

    public function test_show_trip_includes_relations_and_flags(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = Trip::factory()->for($owner, 'user')->create();
        $trip->tripUsers()->attach([$owner->id, $member->id]);

        $task = Task::factory()->for($trip)->for($owner, 'user')->create();
        $task->taskUsers()->attach([$owner->id, $member->id]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/trips/{$trip->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'trip_users',
                'tasks' => [
                    '*' => [
                        'task_users',
                        'can_edit_task',
                    ],
                ],
                'can_edit_trip',
            ])
            ->assertJsonPath('can_edit_trip', true)
            ->assertJsonPath('tasks.0.can_edit_task', true);
    }

    public function test_update_trip_requires_authentication(): void
    {
        $trip = Trip::factory()->create();

        $response = $this->putJson("/api/trips/{$trip->id}", [
            'title' => 'Nowy tytul',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_trip_validates_input(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'user')->create();
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/trips/{$trip->id}", [
            'start_date' => '2025-04-10',
            'end_date' => '2025-04-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_update_trip_updates_record(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'user')->create([
            'title' => 'Stary tytul',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/trips/{$trip->id}", [
            'title' => 'Nowy tytul',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Nowy tytul']);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'title' => 'Nowy tytul',
        ]);
    }

    public function test_delete_trip_requires_authentication(): void
    {
        $trip = Trip::factory()->create();

        $response = $this->deleteJson("/api/trips/{$trip->id}");

        $response->assertStatus(401);
    }

    public function test_delete_trip_returns_404_for_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/trips/999999');

        $response->assertStatus(404);
    }

    public function test_delete_trip_removes_trip_and_related(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'user')->create();
        $trip->tripUsers()->attach($user->id);

        $task = Task::factory()->for($trip)->for($user, 'user')->create();
        $task->taskUsers()->attach($user->id);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/trips/{$trip->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('trip_user', ['trip_id' => $trip->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('task_user', ['task_id' => $task->id, 'user_id' => $user->id]);
    }

    public function test_invite_requires_authentication(): void
    {
        $trip = Trip::factory()->create();

        $response = $this->postJson("/api/trips/{$trip->id}/invite");

        $response->assertStatus(401);
    }

    public function test_invite_creates_invitation_and_returns_link(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'user')->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/trips/{$trip->id}/invite");

        $response->assertStatus(200)
            ->assertJsonStructure(['link']);

        $link = $response->json('link');
        $this->assertStringContainsString((string) $trip->id, $link);
        $this->assertStringContainsString('invite_token=', $link);

        $token = explode('invite_token=', $link)[1] ?? '';

        $this->assertTrue(Str::isUuid($token));
        $this->assertDatabaseHas('trip_invitations', [
            'trip_id' => $trip->id,
            'token' => $token,
        ]);
    }

    public function test_accept_invitation_requires_authentication(): void
    {
        $invitation = TripInvitation::factory()->create();

        $response = $this->postJson('/api/trip-invite/accept', [
            'token' => $invitation->token,
        ]);

        $response->assertStatus(401);
    }

    public function test_accept_invitation_returns_404_for_missing_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/trip-invite/accept', [
            'token' => 'missing-token',
        ]);

        $response->assertStatus(404);
    }

    public function test_accept_invitation_attaches_user_without_detaching(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = Trip::factory()->for($owner, 'user')->create();
        $trip->tripUsers()->attach($owner->id);

        $invitation = TripInvitation::factory()->for($trip)->create();

        Sanctum::actingAs($member);

        $response = $this->postJson('/api/trip-invite/accept', [
            'token' => $invitation->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
        ]);
        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_delete_user_requires_authentication(): void
    {
        $trip = Trip::factory()->create();
        $user = User::factory()->create();
        $trip->tripUsers()->attach($user->id);

        $response = $this->deleteJson("/api/trips/{$trip->id}/deleteuser/{$user->id}");

        $response->assertStatus(401);
    }

    public function test_delete_user_detaches_user(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = Trip::factory()->for($owner, 'user')->create();
        $trip->tripUsers()->attach([$owner->id, $member->id]);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/trips/{$trip->id}/deleteuser/{$member->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $member->id,
        ]);
        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
        ]);
    }
}

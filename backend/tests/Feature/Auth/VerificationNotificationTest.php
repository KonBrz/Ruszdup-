<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_notification_requires_authentication(): void
    {
        $response = $this->post('/email/verification-notification');

        $response->assertStatus(302);
    }

    public function test_verification_notification_sends_email_for_unverified_user(): void
    {
        Mail::fake();
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->postJson('/email/verification-notification');

        $response->assertStatus(200)
            ->assertJson(['status' => 'verification-link-sent']);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verification_notification_redirects_for_verified_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertRedirect('/dashboard');
    }

    public function test_verification_notification_is_throttled(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->postJson('/email/verification-notification')->assertStatus(200);
        }

        $this->actingAs($user)->postJson('/email/verification-notification')->assertStatus(429);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_chat_is_throttled_after_limit(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        RateLimiter::clear('ai|'.$user->id);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/ai-chat', ['prompt' => 'test'])
                ->assertStatus(200);
        }

        $this->postJson('/api/ai-chat', ['prompt' => 'test'])
            ->assertStatus(429);
    }
}

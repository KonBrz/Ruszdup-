<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class AiApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Test response']]]],
                ],
            ], 200),
        ]);
    }

    public function test_ai_ask_requires_authentication(): void
    {
        $response = $this->postJson('/api/ai/ask', []);

        $response->assertStatus(401);
    }

    public function test_ai_ask_validates_destination(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/ask', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['destination']);
    }

    public function test_ai_ask_returns_fake_response_when_configured(): void
    {
        config(['services.gemini.fake' => true]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/ask', [
            'destination' => 'Berlin',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['suggestions']);
    }

    #[Group('ai-real')]
    public function test_ai_ask_returns_500_on_http_error(): void
    {
        if (env('GEMINI_FAKE')) {
            $this->markTestSkipped('GEMINI_FAKE is enabled.');
        }
        config(['services.gemini.fake' => false, 'services.gemini.key' => 'test-key']);
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response('error', 500),
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/ask', [
            'destination' => 'Berlin',
        ]);

        if (config('services.gemini.fake')) {
            $response->assertStatus(200)
                ->assertJsonStructure(['suggestions']);
        } else {
            $response->assertStatus(500)
                ->assertJsonStructure(['response', 'ai_error_status']);
        }
    }

    public function test_ai_chat_requires_authentication(): void
    {
        $response = $this->postJson('/api/ai-chat', []);

        $response->assertStatus(401);
    }

    public function test_ai_chat_validates_prompt(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai-chat', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['prompt']);
    }

    public function test_ai_chat_returns_fake_response_when_configured(): void
    {
        config(['services.gemini.fake' => true]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai-chat', [
            'prompt' => 'Porada',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['response']);
    }

    #[Group('ai-real')]
    public function test_ai_chat_returns_500_on_http_error(): void
    {
        if (env('GEMINI_FAKE')) {
            $this->markTestSkipped('GEMINI_FAKE is enabled.');
        }
        config(['services.gemini.fake' => false, 'services.gemini.key' => 'test-key']);
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response('error', 500),
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai-chat', [
            'prompt' => 'Porada',
        ]);

        if (config('services.gemini.fake')) {
            $response->assertStatus(200)
                ->assertJsonStructure(['response']);
        } else {
            $response->assertStatus(500)
                ->assertJsonStructure(['response', 'ai_error_status']);
        }
    }
}

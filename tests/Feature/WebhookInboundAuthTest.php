<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Models\WebhookSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebhookInboundAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_requires_correct_api_key()
    {
        $team = Team::factory()->create();
        $apiKey = Str::random(32);

        $source = WebhookSource::create([
            'team_id' => $team->id,
            'name' => 'Test Source',
            'platform' => 'custom',
            'auth_method' => 'api_key',
            'auth_config' => json_encode(['key' => $apiKey, 'header' => 'X-API-Key']),
            'is_active' => true,
        ]);

        // 1. Test without header (401)
        $response = $this->postJson("/api/v1/webhooks/inbound/{$source->slug}", ['test' => 'data']);
        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Authentication failed']);

        // 2. Test with wrong header (401)
        $response = $this->postJson("/api/v1/webhooks/inbound/{$source->slug}", ['test' => 'data'], [
            'X-API-Key' => 'wrong-key'
        ]);
        $response->assertStatus(401);

        // 3. Test with correct header (200)
        $response = $this->postJson("/api/v1/webhooks/inbound/{$source->slug}", ['test' => 'data'], [
            'X-API-Key' => $apiKey
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
    }

    public function test_webhook_auth_handles_string_config_gracefully()
    {
        $service = new \App\Services\WebhookAuthService();
        $request = new \Illuminate\Http\Request();

        // This used to throw TypeError, now it should handle string/JSON or fallback to empty array
        $result = $service->verify($request, 'api_key', '{"key":"secret"}');
        $this->assertFalse($result); // Missing header = false, but shouldn't crash

        $result = $service->verify($request, 'none', 'some-string');
        $this->assertTrue($result);
    }
}

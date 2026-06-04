<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->teams()->attach($this->team, ['role' => 'admin']);
        $this->user->forceFill(['current_team_id' => $this->team->id])->save();

        Sanctum::actingAs($this->user);
    }

    public function test_can_get_ai_settings()
    {
        // Setup existing settings
        $teamId = $this->team->id;
        set_setting("ai_provider_{$teamId}", 'openai', 'ai_settings');
        set_setting("ai_model_{$teamId}", 'gpt-4o', 'ai_settings');
        set_setting("ai_confidence_threshold_{$teamId}", '0.85', 'ai_settings');

        $response = $this->getJson('/api/v1/mobile/ai/settings', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'enabled' => (bool) $this->team->ai_auto_reply_enabled,
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'confidence_threshold' => 0.85,
        ]);
    }

    public function test_can_update_ai_settings()
    {
        $payload = [
            'enabled' => true,
            'provider' => 'gemini',
            'api_key' => 'test-gemini-key',
            'model' => 'gemini-1.5-flash',
            'persona' => 'You are a custom AI agent.',
            'use_kb' => true,
            'kb_strict' => true,
            'confidence_threshold' => 0.6,
            'operating_hours_only' => true,
        ];

        $response = $this->postJson('/api/v1/mobile/ai/settings', $payload, [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'AI settings updated successfully!');

        // Check if Team model was updated
        $this->team->refresh();
        $this->assertTrue((bool)$this->team->ai_auto_reply_enabled);

        // Check if Settings table has records using the helpers
        $teamId = $this->team->id;
        $this->assertEquals('gemini', get_setting("ai_provider_{$teamId}"));
        $this->assertEquals('test-gemini-key', get_setting("ai_gemini_api_key_{$teamId}"));
        $this->assertEquals('gemini-1.5-flash', get_setting("ai_model_{$teamId}"));
        $this->assertEquals('You are a custom AI agent.', get_setting("ai_persona_{$teamId}"));
        $this->assertEquals('1', get_setting("ai_use_kb_{$teamId}"));
        $this->assertEquals('1', get_setting("ai_kb_strict_{$teamId}"));
        $this->assertEquals('0.6', get_setting("ai_confidence_threshold_{$teamId}"));
        $this->assertEquals('1', get_setting("ai_operating_hours_only_{$teamId}"));
    }
}

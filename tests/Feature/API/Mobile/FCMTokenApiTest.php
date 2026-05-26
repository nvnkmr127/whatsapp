<?php
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FCMTokenApiTest extends TestCase
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
        $this->user->refresh();

        Sanctum::actingAs($this->user);
    }

    public function test_can_register_fcm_token_pre_tenant()
    {
        $response = $this->postJson('/api/v1/mobile/auth/fcm-token', [
            'token' => 'test_token_pre_tenant',
            'platform' => 'android',
        ], [
            'User-Agent' => 'TestAgent',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_fcm_tokens', [
            'user_id' => $this->user->id,
            'token' => 'test_token_pre_tenant',
            'platform' => 'android',
        ]);

        $token = \App\Models\UserFcmToken::where('token', 'test_token_pre_tenant')->first();
        $this->assertNotNull($token);
        $this->assertEquals('TestAgent', $token->metadata['user_agent'] ?? null);
    }

    public function test_can_remove_fcm_token_pre_tenant()
    {
        $this->user->fcmTokens()->create([
            'token' => 'test_token_pre_tenant_remove',
            'platform' => 'android',
        ]);

        $response = $this->postJson('/api/v1/mobile/auth/fcm-token/remove', [
            'token' => 'test_token_pre_tenant_remove',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('user_fcm_tokens', [
            'token' => 'test_token_pre_tenant_remove',
        ]);
    }

    public function test_can_register_fcm_token_with_tenant()
    {
        $response = $this->postJson('/api/v1/mobile/fcm/register', [
            'token' => 'test_token_with_tenant',
            'platform' => 'ios',
            'device_id' => 'device_123',
        ], [
            'X-Tenant-ID' => $this->team->id,
            'User-Agent' => 'TestAgentIOS',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'token' => ['id', 'user_id', 'token', 'platform', 'device_id', 'metadata', 'last_used_at'],
            ]);

        $this->assertDatabaseHas('user_fcm_tokens', [
            'user_id' => $this->user->id,
            'token' => 'test_token_with_tenant',
            'platform' => 'ios',
            'device_id' => 'device_123',
        ]);

        $token = \App\Models\UserFcmToken::where('token', 'test_token_with_tenant')->first();
        $this->assertNotNull($token);
        $this->assertEquals('TestAgentIOS', $token->metadata['user_agent'] ?? null);
    }

    public function test_can_remove_fcm_token_with_tenant()
    {
        $this->user->fcmTokens()->create([
            'token' => 'test_token_with_tenant_remove',
            'platform' => 'ios',
        ]);

        $response = $this->postJson('/api/v1/mobile/fcm/remove', [
            'token' => 'test_token_with_tenant_remove',
        ], [
            'X-Tenant-ID' => $this->team->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('user_fcm_tokens', [
            'token' => 'test_token_with_tenant_remove',
        ]);
    }

    public function test_fcm_registration_validation_errors()
    {
        $response = $this->postJson('/api/v1/mobile/auth/fcm-token', [
            'platform' => 'invalid_platform',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'platform']);
    }
}

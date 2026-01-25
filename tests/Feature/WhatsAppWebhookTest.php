<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear cache before each test
        Cache::flush();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_verifies_webhook_with_correct_token_from_database()
    {
        // Arrange: Set up verify token in database
        Setting::create([
            'key' => 'whatsapp_webhook_verify_token',
            'value' => 'test_verify_token_123',
        ]);

        // Act: Send verification request
        $response = $this->get('/api/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=test_verify_token_123&hub.challenge=challenge_string_xyz');

        // Assert: Should return the challenge
        $response->assertStatus(200);
        $this->assertEquals('challenge_string_xyz', $response->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_rejects_webhook_with_incorrect_token()
    {
        // Arrange: Set up verify token in database
        Setting::create([
            'key' => 'whatsapp_webhook_verify_token',
            'value' => 'correct_token',
        ]);

        // Act: Send verification request with wrong token
        $response = $this->get('/api/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=wrong_token&hub.challenge=challenge_string');

        // Assert: Should return 403
        $response->assertStatus(403);
        $this->assertEquals('Forbidden', $response->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_rejects_webhook_with_wrong_mode()
    {
        // Arrange: Set up verify token in database
        Setting::create([
            'key' => 'whatsapp_webhook_verify_token',
            'value' => 'test_token',
        ]);

        // Act: Send verification request with wrong mode
        $response = $this->get('/api/webhook/whatsapp?hub.mode=unsubscribe&hub.verify_token=test_token&hub.challenge=challenge_string');

        // Assert: Should return 403
        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_falls_back_to_config_when_database_token_is_empty()
    {
        // Arrange: No token in database, so it should use config default
        // The config default is 'my-secret-token'

        // Act: Send verification request with config default token
        $response = $this->get('/api/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=my-secret-token&hub.challenge=challenge_string');

        // Assert: Should return the challenge
        $response->assertStatus(200);
        $this->assertEquals('challenge_string', $response->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_reads_token_from_settings_table_not_config()
    {
        // Arrange: Set a different token in database than config default
        Setting::create([
            'key' => 'whatsapp_webhook_verify_token',
            'value' => 'database_token',
        ]);

        // Act: Try with config default token (should fail)
        $responseWithConfigToken = $this->get('/api/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=my-secret-token&hub.challenge=challenge_string');

        // Assert: Should reject config token
        $responseWithConfigToken->assertStatus(403);

        // Act: Try with database token (should succeed)
        $responseWithDbToken = $this->get('/api/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=database_token&hub.challenge=challenge_string');

        // Assert: Should accept database token
        $responseWithDbToken->assertStatus(200);
        $this->assertEquals('challenge_string', $responseWithDbToken->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_rejects_webhook_post_with_invalid_signature()
    {
        // Arrange
        config(['whatsapp.app_secret' => 'test_secret']);
        $payload = ['object' => 'whatsapp_business_account', 'entry' => []];

        // Act & Assert
        $response = $this->withHeaders([
            'X-Hub-Signature-256' => 'sha256=invalid_hash'
        ])->postJson('/api/webhook/whatsapp', $payload);

        $response->assertStatus(403);
        $this->assertEquals('Invalid Signature', $response->getContent());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_webhook_post_with_valid_signature()
    {
        // Arrange
        $secret = 'test_secret';
        config(['whatsapp.app_secret' => $secret]);
        $payload = ['object' => 'whatsapp_business_account', 'entry' => []];
        $jsonPayload = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $secret);

        // Act
        $response = $this->withHeaders([
            'X-Hub-Signature-256' => $signature
        ])->postJson('/api/webhook/whatsapp', $payload);

        // Assert
        $response->assertStatus(200);
        $this->assertEquals('EVENT_RECEIVED', $response->getContent());
    }
}

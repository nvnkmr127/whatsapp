<?php

namespace Tests\Feature;

use App\Core\WhatsApp\WhatsAppClient;
use App\Enums\IntegrationState;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppPermissionErrorTest extends TestCase
{
    use RefreshDatabase;

    protected Team $team;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->personalTeam();
        
        $this->team->update([
            'whatsapp_access_token' => 'test-token',
            'whatsapp_phone_number_id' => '12345',
            'whatsapp_business_account_id' => '67890',
            'whatsapp_setup_state' => IntegrationState::READY,
        ]);
    }

    /** @test */
    public function it_suspends_team_when_permission_error_200_is_received()
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => '(#200) You do not have the necessary permissions to send messages on behalf of this WhatsApp Business Account',
                    'type' => 'OAuthException',
                    'code' => 200,
                    'fbtrace_id' => 'AZfAZ5z1aPi2D6mHbWlE1pH'
                ]
            ], 403),
        ]);

        $client = app(WhatsAppClient::class);
        $client->forTeam($this->team);
        $phone = '+911234567890';
        
        $result = $client->sendRequest('messages', ['to' => $phone, 'text' => 'Hello']);
        
        $this->assertFalse($result['success']);
        $this->assertEquals(200, $result['error']['error']['code']);
        
        // Verify team is suspended
        $this->team->refresh();
        $this->assertEquals(IntegrationState::SUSPENDED, $this->team->whatsapp_setup_state);
    }

    /** @test */
    public function it_throws_descriptive_exception_in_send_message_job_on_code_200()
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => '(#200) Permission error',
                    'code' => 200,
                ]
            ], 403),
        ]);

        $this->mock(\App\Services\PolicyService::class, function ($mock) {
            $mock->shouldReceive('canSendFreeMessage')->andReturn(true);
        });

        $phone = '+911234567890';
        $contact = \App\Models\Contact::factory()->create([
            'team_id' => $this->team->id,
            'phone_number' => $phone,
            'last_interaction_at' => now(),
            'opt_in_status' => 'opted_in'
        ]);
        $conversation = \App\Models\Conversation::factory()->create(['team_id' => $this->team->id, 'contact_id' => $contact->id]);

        $message = Message::create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'type' => 'text',
            'direction' => 'outbound',
            'status' => 'queued',
            'content' => 'Test message',
        ]);

        $job = new \App\Jobs\SendMessageJob($this->team->id, '123456789', 'text', 'Test message', null, 'en_US', $message->id);
        
        try {
            $job->handle(app(\App\Services\WhatsAppService::class));
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('WhatsApp Permission Error (#200)', $e->getMessage());
            $this->assertStringContainsString('Please reconnect Facebook', $e->getMessage());
        }
    }
}

<?php

namespace Tests\Feature;

use App\Events\MessageReceived;
use App\Listeners\AiCommerceListener;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Team;
use App\Services\AiCommerceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class VoiceNoteAiReplyTest extends TestCase
{
    use RefreshDatabase;

    private function listenerExpecting(?string $expectedText): AiCommerceListener
    {
        $service = Mockery::mock(AiCommerceService::class);

        if ($expectedText === null) {
            $service->shouldNotReceive('handle');
        } else {
            $service->shouldReceive('handle')
                ->once()
                ->with(Mockery::type(Contact::class), $expectedText)
                ->andReturn(true);
        }

        return new AiCommerceListener($service);
    }

    private function inbound(array $attrs): Message
    {
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        return Message::factory()->create(array_merge([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'direction' => 'inbound',
        ], $attrs));
    }

    public function test_voice_note_with_transcription_is_sent_to_ai_as_text()
    {
        $message = $this->inbound([
            'type' => 'audio',
            'content' => null,
            'metadata' => ['transcription' => 'मुझे एक लाल शर्ट चाहिए'],
        ]);

        $this->listenerExpecting('मुझे एक लाल शर्ट चाहिए')->handle(new MessageReceived($message));
    }

    public function test_audio_without_transcription_is_ignored()
    {
        $message = $this->inbound([
            'type' => 'audio',
            'content' => null,
            'metadata' => [],
        ]);

        $this->listenerExpecting(null)->handle(new MessageReceived($message));
    }

    public function test_text_message_still_routes_to_ai()
    {
        $message = $this->inbound([
            'type' => 'text',
            'content' => 'do you have red shirts',
        ]);

        $this->listenerExpecting('do you have red shirts')->handle(new MessageReceived($message));
    }

    public function test_outbound_audio_is_ignored()
    {
        $message = $this->inbound([
            'type' => 'audio',
            'direction' => 'outbound',
            'metadata' => ['transcription' => 'internal note'],
        ]);

        $this->listenerExpecting(null)->handle(new MessageReceived($message));
    }
}

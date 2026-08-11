<?php

namespace Tests\Feature;

use App\Livewire\Chat\MessageWindow;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatRetryMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_message_re_queues_existing_failed_database_message(): void
    {
        $team = Team::factory()->create(['is_sandbox_mode' => true]);
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '15551234567']);
        $conversation = Conversation::factory()->create(['team_id' => $team->id, 'contact_id' => $contact->id]);

        $failedMessage = Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'status' => 'failed',
            'type' => 'text',
            'content' => 'Retry me',
            'error_message' => 'Previous failure',
        ]);

        $this->actingAs($user);

        $result = Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id])
            ->call('retryMessage', $failedMessage->id)
            ->get('id'); // Call renderless action and get returned array via response

        $res = Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id])
            ->instance()
            ->retryMessage($failedMessage->id);

        $this->assertEquals('sent', $res['status']);
        $failedMessage->refresh();
        $this->assertEquals('sent', $failedMessage->status);
        $this->assertNull($failedMessage->error_message);
    }
}

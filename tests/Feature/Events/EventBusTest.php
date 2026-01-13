<?php

namespace Tests\Feature\Events;

use App\Events\ConversationOpened;
use App\Events\ConversationClosed;
use App\Events\ContactOptedOut;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Team;
use App\Services\ConsentService;
use App\Services\ConversationService;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventBusTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_opened_event_dispatched_on_creation()
    {
        Event::fake();

        $contact = Contact::factory()->create();
        $service = new ConversationService();

        $service->ensureActiveConversation($contact);

        Event::assertDispatched(ConversationOpened::class, function ($e) use ($contact) {
            return $e->conversation->contact_id === $contact->id;
        });
    }

    public function test_conversation_closed_event_dispatched_on_close()
    {
        Event::fake();

        $contact = Contact::factory()->create();
        $conversation = Conversation::create([
            'team_id' => $contact->team_id,
            'contact_id' => $contact->id,
            'status' => 'open'
        ]);

        $service = new ConversationService();
        $service->close($conversation);

        Event::assertDispatched(ConversationClosed::class, function ($e) use ($conversation) {
            return $e->conversation->id === $conversation->id;
        });
    }

    public function test_contact_opted_out_event_dispatched()
    {
        Event::fake();

        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);
        $service = new ConsentService();

        $service->optOut($contact);

        Event::assertDispatched(ContactOptedOut::class, function ($e) use ($contact) {
            return $e->contact->id === $contact->id;
        });
    }
}

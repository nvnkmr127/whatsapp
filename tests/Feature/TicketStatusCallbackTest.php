<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TicketStatusCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Block any real network from the citizen-notification path; the endpoint's
        // try/catch swallows notify failures, so status updates regardless.
        Http::fake();
    }

    private function makeTicket(array $ticketSettings = []): Ticket
    {
        $team = Team::factory()->create(['ticket_settings' => $ticketSettings]);
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        return Ticket::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'ticket_number' => 'TCK-260912-TEST',
            'subject' => 'Test',
            'category' => 'sanitation',
            'priority' => 'medium',
            'status' => 'open',
            'source' => 'bot_automation',
        ]);
    }

    /** Fail closed: with no callback key configured the endpoint must reject, not update. */
    public function test_rejects_when_no_callback_key_configured()
    {
        $ticket = $this->makeTicket([]); // no callback_api_key

        $res = $this->postJson('/api/v1/tickets/status-update', [
            'ticket_number' => $ticket->ticket_number,
            'status' => 'resolved',
        ]);

        $res->assertStatus(403);
        $this->assertSame('open', $ticket->fresh()->status);
    }

    /** A wrong key is rejected. */
    public function test_rejects_wrong_key()
    {
        $ticket = $this->makeTicket(['callback_api_key' => 'right-key']);

        $res = $this->postJson('/api/v1/tickets/status-update', [
            'ticket_number' => $ticket->ticket_number,
            'status' => 'resolved',
        ], ['X-Callback-Key' => 'wrong-key']);

        $res->assertStatus(401);
        $this->assertSame('open', $ticket->fresh()->status);
    }

    /** Correct key updates the status. */
    public function test_accepts_correct_key_and_updates_status()
    {
        $ticket = $this->makeTicket(['callback_api_key' => 'right-key']);

        $res = $this->postJson('/api/v1/tickets/status-update', [
            'ticket_number' => $ticket->ticket_number,
            'status' => 'resolved',
        ], ['X-Callback-Key' => 'right-key']);

        $res->assertStatus(200);
        $this->assertSame('resolved', $ticket->fresh()->status);
    }
}

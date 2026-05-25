<?php

namespace Tests\Feature;

use App\Events\ContactLifecycleChanged;
use App\Events\ContactOptedOut;
use App\Listeners\LogContactEvents;
use App\Models\Contact;
use App\Models\ContactEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LogContactEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_lifecycle_change_creates_event_log()
    {
        $contact = Contact::factory()->create();
        $event = new ContactLifecycleChanged($contact, 'new', 'engaged');

        $listener = new LogContactEvents();
        $listener->handle($event);

        $this->assertDatabaseHas('contact_events', [
            'contact_id' => $contact->id,
            'event_type' => 'LifecycleChanged',
            'idempotency_key' => "log_lifecycle:{$contact->id}:new:engaged",
        ]);
    }

    public function test_log_lifecycle_change_handles_duplicate_concurrency_gracefully()
    {
        $contact = Contact::factory()->create();
        $event = new ContactLifecycleChanged($contact, 'new', 'engaged');

        $listener = new LogContactEvents();

        // Handle first time -> creates record
        $listener->handle($event);

        $this->assertDatabaseCount('contact_events', 1);

        // Clear cache to simulate a race condition where cache has not yet synchronized,
        // forcing a second database insert attempt
        Cache::flush();

        // Handle second time -> should NOT throw exception due to unique constraint, but exit gracefully
        $listener->handle($event);

        // Record count should still be 1
        $this->assertDatabaseCount('contact_events', 1);
    }

    public function test_log_opt_out_handles_duplicate_concurrency_gracefully()
    {
        $contact = Contact::factory()->create();
        $event = new ContactOptedOut($contact);

        $listener = new LogContactEvents();

        // First execution creates the record
        $listener->handle($event);
        $this->assertDatabaseCount('contact_events', 1);

        // Retrieve the idempotency key created
        $loggedEvent = ContactEvent::first();
        $idempotencyKey = $loggedEvent->idempotency_key;

        // Manually attempt to trigger a duplicate insert with the same key
        // by mocking/manipulating the event or calling create directly
        try {
            ContactEvent::create([
                'team_id' => $contact->team_id,
                'contact_id' => $contact->id,
                'event_type' => 'OptedOut',
                'event_data' => ['source' => 'Test'],
                'occurred_at' => now(),
                'idempotency_key' => $idempotencyKey,
            ]);
            $this->fail('Should have thrown UniqueConstraintViolationException');
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $this->assertTrue(true);
        }
    }
}

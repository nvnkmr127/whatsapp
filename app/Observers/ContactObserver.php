<?php

namespace App\Observers;

use App\Models\Contact;
use App\Services\ContactStateManager;
use App\Services\ContactResolver;
use App\Events\ContactUpdated;
use Illuminate\Support\Facades\Log;

class ContactObserver
{
    protected $stateManager;

    public function __construct(ContactStateManager $stateManager)
    {
        $this->stateManager = $stateManager;
    }

    /**
     * Handle the Contact "created" event.
     */
    public function created(Contact $contact): void
    {
        // Initialize state for new contact
        $contact->update([
            'lifecycle_state' => 'new',
            'engagement_score' => 0,
            'message_count' => 0,
            'inbound_message_count' => 0,
            'outbound_message_count' => 0,
            'conversation_count' => 0,
        ]);

        Log::info("Contact created", ['contact_id' => $contact->id]);
    }

    /**
     * Handle the Contact "updated" event.
     */
    public function updated(Contact $contact): void
    {
        // Get changed fields
        $changes = array_keys($contact->getDirty());

        // Recalculate derived fields if relevant fields changed
        if ($contact->wasChanged(['opt_in_status', 'last_interaction_at', 'last_customer_message_at'])) {
            // Queue async recalculation to avoid blocking
            \App\Jobs\RecalculateContactMetrics::dispatch($contact)
                ->delay(now()->addSeconds(5));
        }

        // Update consent age
        if ($contact->wasChanged('opt_in_at') && $contact->opt_in_at) {
            $contact->consent_age_days = now()->diffInDays($contact->opt_in_at);
            $contact->saveQuietly(); // Prevent infinite loop
        }

        // Broadcast update event for real-time UI updates
        if (!empty($changes)) {
            event(new \App\Events\ContactUpdated($contact, $changes));
        }

        // Invalidate cache
        app(\App\Services\ContactResolver::class)->invalidateCache($contact);
    }

    /**
     * Handle the Contact "updating" event.
     */
    public function updating(Contact $contact): void
    {
        // Increment version for optimistic locking
        if ($contact->isDirty() && !$contact->isDirty('version')) {
            $contact->version++;
        }
    }
}

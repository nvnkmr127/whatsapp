<?php

namespace App\Observers;

use App\Models\Contact;
use App\Models\Deal;
use App\Services\WorkflowEngine;

class WorkflowObserver
{
    protected $engine;

    public function __construct(WorkflowEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Handle the Contact "created" event.
     */
    public function created(Contact $contact): void
    {
        $this->engine->trigger('contact_created', $contact);
    }

    /**
     * Handle the Contact "updated" event.
     */
    public function updated(Contact $contact): void
    {
        // Check for tag changes (requires pivoting/syncing observation which is tricky with Eloquent events)
        // For standard updates:
        $this->engine->trigger('contact_updated', $contact);
    }

    /**
     * Handle Deal events (we'll bind this manually or via another observer)
     */
}

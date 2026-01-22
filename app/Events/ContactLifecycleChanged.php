<?php

namespace App\Events;

use App\Models\Contact;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactLifecycleChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contact;
    public $oldState;
    public $newState;

    /**
     * Create a new event instance.
     */
    public function __construct(Contact $contact, string $oldState, string $newState)
    {
        $this->contact = $contact;
        $this->oldState = $oldState;
        $this->newState = $newState;
    }
}

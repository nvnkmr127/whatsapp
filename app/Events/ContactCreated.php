<?php

namespace App\Events;

use App\Models\Contact;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contact;

    public $context;

    /**
     * Create a new event instance.
     */
    public function __construct(Contact $contact, array $context = ['source' => 'system'])
    {
        $this->contact = $contact;
        $this->context = $context;
    }
}

<?php

namespace App\Events;

use App\Events\Base\DomainEvent;
use App\Models\Contact;
use Illuminate\Broadcasting\InteractsWithSockets;

class ContactLifecycleChanged extends DomainEvent
{
    use InteractsWithSockets;

    public $contact;
    public $oldState;
    public $newState;

    public function __construct(Contact $contact, string $oldState, string $newState)
    {
        $this->contact = $contact;
        $this->oldState = $oldState;
        $this->newState = $newState;

        parent::__construct([
            'contact_id' => $contact->id,
            'old_state' => $oldState,
            'new_state' => $newState
        ], [
            'team_id' => $contact->team_id
        ]);
    }

    public function source(): string
    {
        return 'crm';
    }

    public function category(): string
    {
        return 'business';
    }

    public function rules(): array
    {
        return [
            'contact_id' => 'required|integer',
            'old_state' => 'required|string',
            'new_state' => 'required|string'
        ];
    }
}

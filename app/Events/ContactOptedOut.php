<?php

namespace App\Events;

use App\Events\Base\DomainEvent;
use App\Models\Contact;

class ContactOptedOut extends DomainEvent
{
    public $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;

        parent::__construct([
            'contact_id' => $contact->id,
            'phone_number' => $contact->phone_number
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
            'phone_number' => 'required|string'
        ];
    }
}

<?php

namespace App\Events;

use App\Models\Contact;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactOptedOut
{
    use Dispatchable, SerializesModels;

    public $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }
}

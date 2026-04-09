<?php

namespace App\Core\Automations\NodeHandlers;

use App\Core\Automations\NodeHandlerInterface;
use App\Models\AutomationRun;
use App\Models\Contact;
use App\Services\WhatsAppService;

class MessageNodeHandler implements NodeHandlerInterface
{
    protected WhatsAppService $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function handle(Contact $contact, AutomationRun $run, array $nodeData): array
    {
        $text = $nodeData['data']['text'] ?? '';

        // Dynamic Variable Replacement (Legacy support)
        // e.g., {{name}} -> $contact->name
        $text = str_replace('{{name}}', $contact->name ?: 'there', $text);

        $this->whatsapp->sendText($contact->phone_number, $text);

        return ['status' => 'completed'];
    }
}

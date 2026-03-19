<?php

namespace App\Core\Automations\NodeHandlers;

use App\Models\Contact;
use App\Models\AutomationRun;
use App\Core\Automations\NodeHandlerInterface;
use App\Services\WhatsAppService;

class QuestionNodeHandler implements NodeHandlerInterface
{
    protected WhatsAppService $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function handle(Contact $contact, AutomationRun $run, array $nodeData): array
    {
        $text = $nodeData['data']['text'] ?? '';
        $options = $nodeData['data']['options'] ?? []; // e.g., ['yes' => 'Yes', 'no' => 'No']
        
        if (!empty($options)) {
            $this->whatsapp->sendInteractiveButtons($contact->phone_number, $text, $options);
        } else {
            $this->whatsapp->sendText($contact->phone_number, $text);
        }

        return [
            'status' => 'waiting_input',
            'state_update' => [
                'current_node_id' => $nodeData['id'], // Keep current node until answered
                'waiting_for' => 'input'
            ]
        ];
    }
}

<?php

namespace App\Factories;

use Illuminate\Support\Str;

class EventFactory
{
    /**
     * Create a standardized payload for an inbound message event.
     */
    public static function makeInboundMessage(array $webhookData): array
    {
        $value = $webhookData['entry'][0]['changes'][0]['value'];
        $message = $value['messages'][0];
        $contact = $value['contacts'][0] ?? [];
        $metadata = $value['metadata'];

        return [
            'event_id' => 'evt_' . Str::uuid(),
            'event_type' => 'message.inbound',
            'timestamp' => time(),
            'payload' => [
                'provider_id' => $message['id'],
                'from_phone' => $message['from'],
                'to_phone_id' => $metadata['phone_number_id'],
                'contact_name' => $contact['profile']['name'] ?? null,
                'message_type' => $message['type'],
                'content' => $message, // Raw message content (text, image, etc.)
                'raw_payload' => $value, // Full context if needed
            ]
        ];
    }
}

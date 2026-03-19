<?php

namespace App\Services\WhatsApp;

use App\Models\Team;
use App\Models\Contact;
use App\Models\Message;
use App\Core\WhatsApp\WhatsAppClient;
use Illuminate\Support\Facades\Log;

class MessagingService
{
    protected WhatsAppClient $client;
    protected ?Team $team = null;

    public function __construct(WhatsAppClient $client)
    {
        $this->client = $client;
    }

    public function setTeam(Team $team): self
    {
        $this->team = $team;
        $this->client->forTeam($team);
        return $this;
    }

    /**
     * Send a text message.
     */
    public function sendText(string $to, string $text): array
    {
        return $this->client->sendRequest('messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text]
        ]);
    }

    /**
     * Send a template message.
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode, array $components = []): array
    {
        return $this->client->sendRequest('messages', [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components
            ]
        ]);
    }

    /**
     * Send interactive buttons.
     */
    public function sendButtons(string $to, string $text, array $buttons, ?string $header = null, ?string $footer = null): array
    {
        $btns = array_map(fn($btn) => [
            'type' => 'reply',
            'reply' => ['id' => $btn['id'], 'title' => $btn['title']]
        ], $buttons);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $text],
                'action' => ['buttons' => $btns]
            ]
        ];

        if ($header) $payload['interactive']['header'] = ['type' => 'text', 'text' => $header];
        if ($footer) $payload['interactive']['footer'] = ['text' => $footer];

        return $this->client->sendRequest('messages', $payload);
    }
}

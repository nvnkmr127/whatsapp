<?php

namespace App\Services\WhatsApp;

use App\Models\Team;
use App\Models\Contact;
use App\Core\WhatsApp\WhatsAppClient;
use Illuminate\Support\Facades\Log;

class CallService
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
     * Invite a contact to a group call.
     */
    public function inviteToCall(string $to, string $callId, string $offerToken): array
    {
        return $this->client->sendRequest('messages', [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'call',
            'call' => [
                'id' => $callId,
                'offer_token' => $offerToken,
                'action' => 'invite'
            ]
        ]);
    }

    /**
     * End a call for all participants.
     */
    public function endCall(string $callId): array
    {
        return $this->client->sendRequest('messages', [
            'messaging_product' => 'whatsapp',
            'type' => 'call',
            'call' => [
                'id' => $callId,
                'action' => 'end'
            ]
        ]);
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppAccountRisk
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $type;
    public $payload;
    public $teamId;

    /**
     * Create a new event instance.
     *
     * @param string $type The type of risk (e.g. 'QUALITY_UPDATE', 'BAN', 'RESTRICTION')
     * @param array $payload The raw webhook payload or relevant details
     * @param int|null $teamId The ID of the team affected (if resolvable)
     */
    public function __construct(string $type, array $payload, ?int $teamId = null)
    {
        $this->type = $type;
        $this->payload = $payload;
        $this->teamId = $teamId;
    }
}

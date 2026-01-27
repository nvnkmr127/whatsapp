<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallRestrictionApplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $teamId;
    public $phoneNumberId;
    public $reason;
    public $restrictedUntil;
    public $payload;

    /**
     * Create a new event instance.
     */
    public function __construct($teamId, $phoneNumberId, $reason, $restrictedUntil = null, $payload = [])
    {
        $this->teamId = $teamId;
        $this->phoneNumberId = $phoneNumberId;
        $this->reason = $reason;
        $this->restrictedUntil = $restrictedUntil;
        $this->payload = $payload;
    }
}

<?php

namespace App\Events;

use App\Models\WhatsAppCall;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call;

    public function __construct(WhatsAppCall $call)
    {
        $this->call = $call;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('teams.' . $this->call->team_id);
    }

    public function broadcastAs()
    {
        return 'call.ended';
    }

    public function broadcastWith()
    {
        return [
            'call_id' => $this->call->call_id,
            'duration' => $this->call->duration_seconds,
            'cost' => $this->call->cost_amount,
        ];
    }
}

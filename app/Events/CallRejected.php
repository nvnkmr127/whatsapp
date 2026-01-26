<?php

namespace App\Events;

use App\Models\WhatsAppCall;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call;

    public function __construct(WhatsAppCall $call)
    {
        $this->call = $call;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('team.' . $this->call->team_id);
    }

    public function broadcastAs()
    {
        return 'call.rejected';
    }
}

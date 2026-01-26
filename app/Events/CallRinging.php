<?php

namespace App\Events;

use App\Models\WhatsAppCall;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallRinging implements ShouldBroadcast
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
        return 'call.ringing';
    }

    public function broadcastWith()
    {
        return [
            'call_id' => $this->call->call_id,
            'direction' => $this->call->direction,
            'from' => $this->call->from_number,
            'to' => $this->call->to_number,
            'contact_id' => $this->call->contact_id,
        ];
    }
}

<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $assignedTo;
    public $assignedBy;

    public function __construct(Conversation $conversation, $assignedTo, $assignedBy)
    {
        $this->conversation = $conversation;
        $this->assignedTo = $assignedTo;
        $this->assignedBy = $assignedBy;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('teams.' . $this->conversation->team_id);
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversation->id,
            'contact_name' => $this->conversation->contact->name ?? $this->conversation->contact->phone_number,
            'assigned_to' => $this->assignedTo,
            'assigned_by_name' => $this->assignedBy->name ?? 'System',
        ];
    }
}

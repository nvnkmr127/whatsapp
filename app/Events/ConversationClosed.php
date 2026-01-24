<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationClosed implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $conversation;
    public $closer;

    public function __construct(Conversation $conversation, ?User $closer = null)
    {
        $this->conversation = $conversation;
        $this->closer = $closer;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('teams.' . $this->conversation->team_id),
            new PrivateChannel('conversation.' . $this->conversation->id),
        ];
    }
}
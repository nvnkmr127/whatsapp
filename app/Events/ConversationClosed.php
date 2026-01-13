<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationClosed
{
    use Dispatchable, SerializesModels;

    public $conversation;
    public $closer;

    public function __construct(Conversation $conversation, ?User $closer = null)
    {
        $this->conversation = $conversation;
        $this->closer = $closer;
    }
}

<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationOpened
{
    use Dispatchable, SerializesModels;

    public $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }
}

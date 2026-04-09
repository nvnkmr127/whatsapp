<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MessageReceived';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('teams.'.$this->message->team_id),
            new PresenceChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    /**
     * Data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'direction' => $this->message->direction,
                'content' => $this->message->content,
                'type' => $this->message->type,
                'status' => $this->message->status,
                'created_at' => $this->message->created_at->timestamp,
                'pretty_time' => $this->message->created_at->format('H:i'),
                'media_url' => $this->message->media_url ? (\Illuminate\Support\Facades\Storage::url($this->message->media_url)) : null,
                'media_type' => $this->message->media_type,
                'caption' => $this->message->caption,
                'error_message' => $this->message->error_message,
                'is_outbound' => $this->message->direction === 'outbound',
                'attributed_campaign_name' => $this->message->attributedCampaign?->name,
                'conversation_id' => $this->message->conversation_id,
                'team_id' => $this->message->team_id,
            ],
            'timestamp' => now()->timestamp,
            'sequence_id' => $this->message->id,
        ];
    }
}

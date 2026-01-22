<?php

namespace App\Events;

use App\Models\Contact;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ContactUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $contact;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Contact $contact, array $changes = [])
    {
        $this->contact = $contact;
        $this->changes = $changes;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel("team.{$this->contact->team_id}");
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'contact_id' => $this->contact->id,
            'phone_number' => $this->contact->phone_number,
            'changes' => $this->changes,
            'data' => [
                'id' => $this->contact->id,
                'name' => $this->contact->name,
                'email' => $this->contact->email,
                'opt_in_status' => $this->contact->opt_in_status,
                'engagement_score' => $this->contact->engagement_score,
                'lifecycle_state' => $this->contact->lifecycle_state,
                'assigned_to' => $this->contact->assigned_to,
                'has_pending_reply' => $this->contact->has_pending_reply,
                'version' => $this->contact->version,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ContactUpdated';
    }
}

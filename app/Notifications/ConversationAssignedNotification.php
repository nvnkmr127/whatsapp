<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ConversationAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $conversation;

    protected $assignedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Conversation $conversation, User $assignedBy)
    {
        $this->conversation = $conversation;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification for database.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'contact_name' => $this->conversation->contact->name ?? $this->conversation->contact->phone_number,
            'message' => "You have been assigned a new conversation from {$this->assignedBy->name}.",
            'type' => 'conversation_assigned',
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'conversation_id' => $this->conversation->id,
            'contact_name' => $this->conversation->contact->name ?? $this->conversation->contact->phone_number,
            'message' => 'New assignment: '.($this->conversation->contact->name ?? $this->conversation->contact->phone_number),
            'assigned_by' => $this->assignedBy->name,
        ]);
    }
}

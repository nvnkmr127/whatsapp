<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WhatsAppTokenExpired extends Notification implements ShouldQueue
{
    use Queueable;

    public $team;

    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Action Required: WhatsApp Token Expired')
            ->line('The WhatsApp Business API token for your team "'.$this->team->name.'" has expired.')
            ->line('Please reconnect your WhatsApp account to ensure uninterrupted service.')
            ->action('Reconnect WhatsApp', url('/teams/'.$this->team->id.'/whatsapp-config'));
    }

    public function toArray($notifiable)
    {
        return [
            'team_id' => $this->team->id,
            'message' => 'WhatsApp Token Expired',
        ];
    }
}

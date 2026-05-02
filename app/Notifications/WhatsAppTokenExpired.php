<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WhatsAppTokenExpired extends Notification implements ShouldQueue
{
    use Queueable, \Illuminate\Queue\SerializesModels;

    public $team;

    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', \App\Channels\FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return [
            'title' => '🚨 WhatsApp Token Expired',
            'body' => 'The WhatsApp Business API token for team "'.$this->team->name.'" has expired. Please reconnect.',
            'data' => [
                'type' => 'token_expired',
                'team_id' => (string) $this->team->id,
            ],
        ];
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

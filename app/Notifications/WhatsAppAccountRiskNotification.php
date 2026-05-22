<?php

namespace App\Notifications;

use App\Events\WhatsAppAccountRisk;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WhatsAppAccountRiskNotification extends Notification
{
    use Queueable;

    public function __construct(protected WhatsAppAccountRisk $event) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = match ($this->event->type) {
            'BAN'         => 'CRITICAL: Your WhatsApp Account Has Been Banned',
            'RESTRICTION' => 'WARNING: Your WhatsApp Account Is Restricted',
            default       => 'WhatsApp Account Risk Alert: '.$this->event->type,
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A risk event has been detected on your WhatsApp Business Account.')
            ->line('**Risk type:** '.$this->event->type)
            ->line('**Time:** '.now()->toDateTimeString())
            ->action('Review Account Status', url('/settings/whatsapp'))
            ->line('Please take immediate action to avoid service interruption.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'     => 'whatsapp_account_risk',
            'risk_type' => $this->event->type,
            'team_id'  => $this->event->teamId,
            'payload'  => $this->event->payload ?? [],
        ];
    }
}

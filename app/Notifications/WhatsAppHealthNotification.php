<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WhatsAppHealthNotification extends Notification
{
    use Queueable;

    protected $team;
    protected $alertType;
    protected $message;
    protected $metadata;

    public function __construct(Team $team, string $alertType, string $message, array $metadata = [])
    {
        $this->team = $team;
        $this->alertType = $alertType; // 'quality_red', 'quality_yellow', 'token_expiry', 'webhook_pulse'
        $this->message = $message;
        $this->metadata = $metadata;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = match ($this->alertType) {
            'quality_red' => '🚨 CRITICAL: WhatsApp Account Restricted',
            'quality_yellow' => '⚠️ WARNING: WhatsApp Quality Drop',
            'token_expiry' => '🔑 ACTION REQUIRED: WhatsApp Token Expiring',
            'webhook_pulse' => '📉 ISSUE: WhatsApp Webhooks Interrupted',
            default => '🔔 WhatsApp System Alert'
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->message)
            ->action('View WhatsApp Dashboard', url('/teams/' . $this->team->id . '/whatsapp/setup'))
            ->line('Failure to address this may result in service interruption or Meta account suspension.');
    }

    public function toArray($notifiable): array
    {
        return [
            'team_id' => $this->team->id,
            'alert_type' => $this->alertType,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }
}

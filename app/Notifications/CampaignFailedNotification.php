<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignFailedNotification extends Notification
{
    use Queueable;

    public $campaign;
    public $errorMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct($campaign, $errorMessage)
    {
        $this->campaign = $campaign;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Campaign Failure Alert: ' . ($this->campaign->name ?? 'WhatsApp Campaign'))
            ->line('A critical error has occurred during your campaign broadcast.')
            ->line('Error Message: ' . $this->errorMessage)
            ->action('View Dashboard', url('/campaigns/' . $this->campaign->id . '/live'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name ?? '',
            'error' => $this->errorMessage,
            'icon' => 'exclamation-triangle',
            'type' => 'campaign_failure'
        ];
    }
}

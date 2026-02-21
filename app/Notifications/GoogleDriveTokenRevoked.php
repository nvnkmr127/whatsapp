<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GoogleDriveTokenRevoked extends Notification implements ShouldQueue
{
    use Queueable;

    public $team;
    public $errorMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(Team $team, string $errorMessage = '')
    {
        $this->team = $team;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('CRITICAL: Google Drive Backup Disconnected')
            ->line('The Google Drive integration for your team "' . $this->team->name . '" has been disconnected or the access was revoked.')
            ->line('Reason: ' . ($this->errorMessage ?: 'Authentication failed.'))
            ->line('Your automated backups will NOT be uploaded to Google Drive until this is resolved.')
            ->action('Reconnect Google Drive', url('/settings/integrations'))
            ->line('Please reconnect immediately to ensure your data is safe.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'team_id' => $this->team->id,
            'title' => 'Google Drive Disconnected',
            'message' => 'Cloud backups are failing due to a disconnected Google Drive account.',
            'error' => $this->errorMessage,
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginSecurityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ip;
    protected $userAgent;
    protected $time;

    /**
     * Create a new notification instance.
     */
    public function __construct($ip, $userAgent, $time)
    {
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->time = $time;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Could also add WhatsApp here
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->mailer(\App\Enums\EmailUseCase::ALERT->getMailer())
            ->subject('Security Alert: New Login Detected')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new login was detected for your account from an unrecognized device or location.')
            ->line('**Time:** ' . $this->time)
            ->line('**IP Address:** ' . $this->ip)
            ->line('**Device:** ' . $this->userAgent)
            ->action('Review Recent Activity', route('dashboard'))
            ->line('If this was you, you can safely ignore this message. If not, please change your security settings immediately.')
            ->salutation('Stay secure, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'security_alert',
            'title' => 'New Login Detected',
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
            'time' => $this->time,
        ];
    }
}

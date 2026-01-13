<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPassword extends Notification
{
    /**
     * The password reset token.
     */
    public $token;

    /**
     * The callback that should be used to create the reset password URL.
     */
    public static $createUrlCallback;

    /**
     * The callback that should be used to build the mail message.
     */
    public static $toMailCallback;

    /**
     * Create a notification instance.
     */
    public function __construct(#[\SensitiveParameter] $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        try {
            if (static::$toMailCallback) {
                return call_user_func(static::$toMailCallback, $notifiable, $this->token);
            }

            return $this->buildMailMessage($this->resetUrl($notifiable));
        } catch (\Exception $e) {
            app_log('Error sending reset password notification: ' . $e->getMessage(), 'error', $e, [
                'notifiable_id' => $notifiable->id    ?? null,
                'email'         => $notifiable->email ?? null,
            ]);
        }
    }

    /**
     * Get the reset password notification mail message for the given URL.
     */
    protected function buildMailMessage($url)
    {
        try {
            $template = EmailTemplate::where('slug', 'password-reset')->firstOrFail();

            return (new MailMessage)
                ->subject(Lang::get($template->subject))
                ->line(Lang::get($template->message))
                ->action(Lang::get('Reset Password'), $url)
                ->line(Lang::get('This password reset link will expire in :count minutes.', [
                    'count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire'),
                ]))
                ->line(Lang::get('If you did not request a password reset, no further action is required.'));
        } catch (\Exception $e) {
            app_log('Error building reset password mail message: ' . $e->getMessage(), 'error', $e, [
                'url'     => $url,
                'user_id' => $notifiable->id ?? null,
            ]);
        }
    }

    /**
     * Get the reset URL for the given notifiable.
     */
    protected function resetUrl($notifiable)
    {
        try {
            if (static::$createUrlCallback) {
                return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
            }

            return url(route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        } catch (\Exception $e) {
            app_log('Error generating reset password URL: ' . $e->getMessage(), 'error', $e, [
                'notifiable_id' => $notifiable->id    ?? null,
                'email'         => $notifiable->email ?? null,
            ]);
        }
    }

    /**
     * Set a callback that should be used when creating the reset password button URL.
     */
    public static function createUrlUsing($callback)
    {
        try {
            static::$createUrlCallback = $callback;
        } catch (\Exception $e) {
            app_log('Error setting reset URL callback: ' . $e->getMessage(), 'error', $e);
        }
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     */
    public static function toMailUsing($callback)
    {
        try {
            static::$toMailCallback = $callback;
        } catch (\Exception $e) {
            app_log('Error setting reset mail callback: ' . $e->getMessage(), 'error', $e);
        }
    }
}

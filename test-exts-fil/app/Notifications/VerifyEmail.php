<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Notification
{
    public static $createUrlCallback;

    public static $toMailCallback;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        try {
            $verificationUrl = $this->verificationUrl($notifiable);
            if (static::$toMailCallback) {
                return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl);
            }

            return $this->buildMailMessage($verificationUrl);
        } catch (\Exception $e) {
            app_log('Error sending verify email notification: ' . $e->getMessage(), 'error', $e, [
                'notifiable_id' => $notifiable->id    ?? null,
                'email'         => $notifiable->email ?? null,
            ]);
        }
    }

    protected function buildMailMessage($url)
    {
        try {
            $template = EmailTemplate::where(['slug' => 'email-confirmation'])->first();

            return (new MailMessage)
                ->greeting('Hello, ' . Auth::user()->name)
                ->subject(Lang::get($template->subject))
                ->line(Lang::get($template->message))
                ->action(Lang::get('verify_email_address'), $url);
        } catch (\Exception $e) {
            app_log('Error building mail message: ' . $e->getMessage(), 'error', $e, [
                'url'     => $url,
                'user_id' => Auth::id(),
            ]);
        }
    }

    protected function verificationUrl($notifiable)
    {
        try {
            if (static::$createUrlCallback) {
                return call_user_func(static::$createUrlCallback, $notifiable);
            }

            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id'   => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        } catch (\Exception $e) {
            app_log('Error generating verification URL: ' . $e->getMessage(), 'error', $e, [
                'notifiable_id' => $notifiable->id    ?? null,
                'email'         => $notifiable->email ?? null,
            ]);
        }
    }

    public static function createUrlUsing($callback)
    {
        try {
            static::$createUrlCallback = $callback;
        } catch (\Exception $e) {
            app_log('Error setting verification URL callback: ' . $e->getMessage(), 'error', $e);
        }
    }

    public static function toMailUsing($callback)
    {
        try {
            static::$toMailCallback = $callback;
        } catch (\Exception $e) {
            app_log('Error setting mail callback: ' . $e->getMessage(), 'error', $e);
        }
    }
}

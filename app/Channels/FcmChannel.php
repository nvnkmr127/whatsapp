<?php

namespace App\Channels;

use App\Services\FcmService;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    public function __construct(protected FcmService $fcmService) {}

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        $message = $notification->toFcm($notifiable);

        if (!$message || $notifiable->fcmTokens->isEmpty()) {
            return;
        }

        $this->fcmService->sendToUser(
            $notifiable,
            $message['title'],
            $message['body'],
            $message['data'] ?? []
        );
    }
}

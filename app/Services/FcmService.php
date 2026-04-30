<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    /**
     * Send a notification to all devices registered to a user.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [])
    {
        $tokens = $user->fcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            return;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send a notification to specific FCM tokens.
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [])
    {
        $messaging = app('firebase.messaging');

        $notification = Notification::create($title, $body);
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data)
            ->withAndroidConfig([
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'high_importance_channel',
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
            ])
            ->withApnsConfig([
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ]);

        try {
            $report = $messaging->sendMulticast($message, $tokens);

            if ($report->hasFailures()) {
                foreach ($report->failures() as $failure) {
                    $token = $failure->targetIdentifier();
                    Log::warning("FCM delivery failed for token: {$token}. Error: ".$failure->error()->getMessage());

                    // Cleanup invalid tokens
                    if ($failure->error()->getMessage() === 'Requested entity was not found.') {
                        \App\Models\UserFcmToken::where('token', $token)->delete();
                    }
                }
            }

            return $report;
        } catch (\Exception $e) {
            Log::error('FCM Delivery Error: '.$e->getMessage());

            return null;
        }
    }
}

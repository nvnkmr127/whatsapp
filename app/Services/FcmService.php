<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FcmService
{
    /**
     * Get OAuth2 access token for FCM, cached for 50 minutes.
     */
    protected function credentialsPath(): string
    {
        $fromEnv = env('FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS'));
        // Relative paths are resolved from the project base directory
        return $fromEnv && !str_starts_with($fromEnv, '/')
            ? base_path($fromEnv)
            : ($fromEnv ?: storage_path('app/firebase/service-account.json'));
    }

    protected function getAccessToken(): ?string
    {
        return cache()->remember('fcm_access_token', 3000, function () {
            $credentialsPath = $this->credentialsPath();
            if (!file_exists($credentialsPath)) {
                Log::error("FCM credentials file not found at: {$credentialsPath}");
                return null;
            }

            try {
                $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
                $creds = new ServiceAccountCredentials($scopes, $credentialsPath);
                $authToken = $creds->fetchAuthToken();
                return $authToken['access_token'] ?? null;
            } catch (\Exception $e) {
                Log::error("Failed to generate FCM access token: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get project ID from Firebase credentials, cached for 24 hours.
     */
    protected function getProjectId(): ?string
    {
        return cache()->remember('fcm_project_id', 86400, function () {
            $credentialsPath = $this->credentialsPath();
            if (!file_exists($credentialsPath)) {
                return null;
            }
            $config = json_decode(file_get_contents($credentialsPath), true);
            return $config['project_id'] ?? null;
        });
    }

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
     * Send a notification to specific FCM tokens via FCM HTTP v1 REST API.
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [])
    {
        $accessToken = $this->getAccessToken();
        $projectId = $this->getProjectId();

        if (!$accessToken || !$projectId) {
            Log::error("FCM Send aborted: Missing access token or project ID.");
            return null;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $channelId = (isset($data['type']) && $data['type'] === 'call_incoming') ? 'calls' : 'default';

        $successCount = 0;
        $failures = [];

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_map('strval', $data), // FCM data values must be strings
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => $channelId,
                            'sound' => 'default',
                            'notification_priority' => 'PRIORITY_HIGH',
                        ],
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'alert' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                                'sound' => 'default',
                                'badge' => 1,
                                'content-available' => 1,
                            ],
                        ],
                    ],
                ]
            ];

            try {
                $response = Http::withToken($accessToken)->post($url, $payload);

                if ($response->successful()) {
                    $successCount++;
                } else {
                    $statusCode = $response->status();
                    $errorData = $response->json();
                    $errorMessage = $errorData['error']['message'] ?? 'Unknown error';

                    Log::warning("FCM delivery failed for token: {$token}. HTTP Status: {$statusCode}. Error: {$errorMessage}");

                    // Cleanup invalid/mismatched tokens
                    $errorStatus = $errorData['error']['status'] ?? '';
                    $isInvalidToken = $statusCode === 404
                        || $errorStatus === 'NOT_FOUND'
                        || str_contains($errorMessage, 'Requested entity was not found')
                        || str_contains($errorMessage, 'SenderId mismatch')
                        || $errorStatus === 'INVALID_ARGUMENT';
                    if ($isInvalidToken) {
                        \App\Models\UserFcmToken::where('token', $token)->delete();
                        Log::info("FCM: Deleted invalid token {$token} (reason: {$errorMessage})");
                    }

                    $failures[] = [
                        'token' => $token,
                        'error' => $errorMessage,
                    ];
                }
            } catch (\Exception $e) {
                Log::error("FCM request error for token {$token}: " . $e->getMessage());
                $failures[] = [
                    'token' => $token,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Return a report object compatible with the old Kreait SDK MulticastSendReport
        return new class($successCount, $failures) {
            public function __construct(protected int $successCount, protected array $failures) {}

            public function hasFailures(): bool
            {
                return !empty($this->failures);
            }

            public function failures(): array
            {
                return array_map(function ($f) {
                    return new class($f['token'], $f['error']) {
                        public function __construct(protected string $token, protected string $error) {}

                        public function targetIdentifier(): string
                        {
                            return $this->token;
                        }

                        public function error(): object
                        {
                            return new class($this->error) {
                                public function __construct(protected string $error) {}
                                public function getMessage(): string
                                {
                                    return $this->error;
                                }
                            };
                        }
                    };
                }, $this->failures);
            }
        };
    }
}

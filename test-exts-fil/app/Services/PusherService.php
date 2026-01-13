<?php

namespace App\Services;

use Pusher\Pusher;

class PusherService
{
    protected ?Pusher $pusher = null;

    protected int $connectionRetries = 0;

    protected const MAX_RETRIES = 3;

    public function __construct()
    {
        $this->initializePusher();
    }

    /**
     * Initialize the Pusher client with improved error handling
     */
    protected function initializePusher(): void
    {
        if ($this->connectionRetries >= self::MAX_RETRIES) {
            return;
        }

        try {
            // Get settings with appropriate fallbacks
            $appKey    = get_setting('pusher.app_key');
            $appSecret = get_setting('pusher.app_secret');
            $appId     = get_setting('pusher.app_id');
            $cluster   = get_setting('pusher.cluster', 'mt1');

            // Validate required settings
            if (empty($appKey) || empty($appSecret) || empty($appId) || empty($cluster)) {
                $this->pusher = null;

                return;
            }

            // Initialize Pusher with better options
            $this->pusher = new Pusher(
                $appKey,
                $appSecret,
                $appId,
                [
                    'cluster'   => $cluster,
                    'useTLS'    => true,
                    'host'      => "api-{$cluster}.pusher.com", // Explicitly set the host
                    'port'      => 443,
                    'scheme'    => 'https',
                    'encrypted' => true,
                    'timeout'   => 30,
                    'debug'     => config('app.debug', false),
                ]
            );

            $this->connectionRetries = 0;
        } catch (\Exception $e) {
            $this->pusher = null;
            $this->connectionRetries++;
        }
    }

    /**
     * Trigger an event on a channel
     */
    public function trigger(string $channel, string $event, array $data): array
    {
        if (! $this->isPusherReady()) {
            return ['status' => false, 'message' => 'Pusher initialization failed'];
        }

        try {
            // Always pass an array as the 4th parameter
            $this->pusher->trigger($channel, $event, $data, []);

            return ['status' => true, 'message' => 'Pusher connection test successful'];
        } catch (\Exception $e) {

            // Try to reinitialize on connection issues
            if (strpos($e->getMessage(), 'cURL error 28') !== false || strpos($e->getMessage(), 'Connection') !== false || strpos($e->getMessage(), 'Unable to parse URI') !== false) {
                $this->initializePusher();
            }

            return ['status' => false, 'message' => 'Pusher trigger failed: ' . $e->getMessage()];
        }
    }

    /**
     * Trigger multiple events at once
     */
    public function triggerBatch(array $events): bool
    {
        try {
            if (! $this->isPusherReady()) {
                return false;
            }

            // Always pass an array as the 2nd parameter
            $this->pusher->triggerBatch($events, []);

            return true;
        } catch (\Exception $e) {

            return false;
        }
    }

    /**
     * Authenticate a user for presence channels
     */
    public function authenticateUser(string $socketId, array $channelData): string
    {
        if (! $this->isPusherReady()) {
            throw new \RuntimeException('Pusher not initialized');
        }

        return $this->pusher->authenticateUser($socketId, $channelData);
    }

    /**
     * Authenticate a private or presence channel
     */
    public function authenticateChannel(string $socketId, string $channel, array $data = []): string
    {
        if (! $this->isPusherReady()) {
            throw new \RuntimeException('Pusher not initialized');
        }

        return $this->pusher->authorizeChannel($socketId, $channel, $data);
    }

    /**
     * Get channel information
     */
    public function getChannelInfo(string $channel)
    {
        if (! $this->isPusherReady()) {
            throw new \RuntimeException('Pusher not initialized');
        }

        return $this->pusher->getChannelInfo($channel);
    }

    /**
     * Check if the Pusher client is ready to use
     */
    public function isPusherReady(): bool
    {
        return $this->pusher !== null;
    }

    /**
     * Check Pusher server connection and key validity
     */
    public function testConnection(): array
    {
        if (! $this->isPusherReady()) {
            return [
                'status'  => false,
                'message' => 'Pusher not initialized - please check your Pusher configuration',
            ];
        }

        try {
            $result = $this->pusher->trigger('test-channel', 'test-event', ['message' => 'Connection test'], []);

            if (isset($result['status']) && $result['status'] === 200) {
                return [
                    'status'  => true,
                    'message' => 'Pusher connection test successful!',
                ];
            }

            return [
                'status'  => false,
                'message' => 'Pusher connection test failed',
                'details' => $result,
            ];
        } catch (\Exception $e) {
            // If the error indicates missing or invalid configuration, provide a clearer message
            if (strpos($e->getMessage(), 'Unable to parse URI') !== false) {
                return [
                    'status'  => false,
                    'message' => 'Pusher connection failed: Invalid configuration. Please check your Pusher key, secret, app ID, and cluster settings.',
                ];
            }

            return [
                'status'  => false,
                'message' => 'Pusher test connection failed: ' . $e->getMessage(),
            ];
        }
    }
}

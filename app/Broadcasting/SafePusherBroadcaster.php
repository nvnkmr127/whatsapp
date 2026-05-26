<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Support\Facades\Log;

class SafePusherBroadcaster extends PusherBroadcaster
{
    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        try {
            parent::broadcast($channels, $event, $payload);
        } catch (\Throwable $e) {
            Log::warning('Websocket broadcast failed: ' . $e->getMessage(), [
                'event' => $event,
                'channels' => $channels,
            ]);
        }
    }
}

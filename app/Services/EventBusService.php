<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class EventBusService
{
    /**
     * Check if Redis is enabled.
     */
    protected function isRedisEnabled(): bool
    {
        $client = config('database.redis.client');
        return !empty($client) && $client !== 'null';
    }
    /**
     * Publish an event to a Redis Stream or Database.
     */
    public function publish(string $stream, string $eventType, array $payload): ?string
    {
        // Try Redis first if not explicitly disabled
        if ($this->isRedisEnabled()) {
            try {
                $data = [
                    'event_type' => $eventType,
                    'payload' => json_encode($payload),
                    'timestamp' => time(),
                ];
                $id = Redis::xadd($stream, '*', $data);
                Log::debug("EventBus: Published to Redis {$stream}", ['id' => $id, 'type' => $eventType]);
                return $id;
            } catch (\Exception $e) {
                Log::warning("EventBus: Redis failed, falling back to Database: " . $e->getMessage());
            }
        }

        // Database Fallback (Essential for cPanel/Shared Hosting)
        try {
            $id = \Illuminate\Support\Facades\DB::table('broadcast_events')->insertGetId([
                'event_type' => $eventType,
                'payload' => json_encode($payload),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::debug("EventBus: Published to Database", ['id' => $id, 'type' => $eventType]);
            return (string) $id;
        } catch (\Exception $e) {
            Log::error("EventBus: Failed to publish to Database: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a consumer group (Redis only).
     */
    public function createGroup(string $stream, string $group, string $startFrom = '$'): void
    {
        if (!$this->isRedisEnabled())
            return;

        try {
            Redis::xgroup('CREATE', $stream, $group, $startFrom, 'MKSTREAM');
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'BUSYGROUP')) {
                Log::warning("EventBus: Error creating Redis group {$group}: " . $e->getMessage());
            }
        }
    }

    /**
     * Acknowledge a message (Redis or DB).
     */
    public function ack(string $stream, string $group, array $ids): void
    {
        if ($this->isRedisEnabled()) {
            try {
                Redis::xack($stream, $group, $ids);
                return;
            } catch (\Exception $e) {
                // If it fails, maybe it was a DB event or Redis is down
            }
        }

        \Illuminate\Support\Facades\DB::table('broadcast_events')
            ->whereIn('id', $ids)
            ->update(['status' => 'completed', 'updated_at' => now()]);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EventBusService
{
    /**
     * Publish an event to the Database.
     */
    public function publish(string $stream, string $eventType, array $payload): ?string
    {
        // Database Implementation (Essential for cPanel/Shared Hosting)
        try {
            $id = DB::table('broadcast_events')->insertGetId([
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
     * Acknowledge a message (DB).
     */
    public function ack(string $stream, string $group, array $ids): void
    {
        DB::table('broadcast_events')
            ->whereIn('id', $ids)
            ->update(['status' => 'completed', 'updated_at' => now()]);
    }
}

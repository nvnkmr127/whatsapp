<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventBusService
{
    /**
     * Publish an event to the Database.
     */
    public function publish(string $stream, string $eventType, array $payload, ?int $teamId = null): ?string
    {
        try {
            $id = DB::table('broadcast_events')->insertGetId([
                'team_id' => $teamId,
                'event_type' => $eventType,
                'payload' => json_encode($payload),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return (string) $id;
        } catch (\Exception $e) {
            Log::error('EventBus: Failed to publish to Database: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Publish multiple events to the Database in a single batch.
     */
    public function publishBatch(string $stream, array $events): bool
    {
        if (empty($events)) {
            return true;
        }

        try {
            $data = array_map(function ($event) {
                return [
                    'team_id' => $event['team_id'] ?? null,
                    'event_type' => $event['event_type'],
                    'payload' => json_encode($event['payload']),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $events);

            DB::table('broadcast_events')->insert($data);

            return true;
        } catch (\Exception $e) {
            Log::error('EventBus: Failed to publish batch to Database: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Acknowledge messages (DB).
     */
    public function ack(string $stream, string $group, array $ids): void
    {
        DB::table('broadcast_events')
            ->whereIn('id', $ids)
            ->update(['status' => 'completed', 'updated_at' => now()]);
    }
}

<?php

namespace App\Listeners;

use App\Events\Contracts\DomainEventContract;
use App\Models\SystemEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PersistDomainEvents implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     * 
     * @param string|object $event
     * @param array $payload
     */
    public function handle($event, $payload = []): void
    {
        // Wildcard Event Listener passes ($eventName, $payloadArray)
        // Standard Event Listener passes ($eventObject)

        $eventObj = null;

        if (is_object($event)) {
            $eventObj = $event;
        } elseif (is_string($event) && !empty($payload)) {
            // Wildcard mode: $event is the string name, $payload is array containing the event object(s)
            $eventObj = $payload[0] ?? null;
        }

        // Only handle DomainEvents
        if (!($eventObj instanceof DomainEventContract)) {
            return;
        }

        // Sampling Logic
        if (!$this->shouldPersist($eventObj)) {
            return;
        }

        $metadata = $eventObj->metadata ?? [];

        SystemEvent::create([
            'event_id' => $eventObj->eventId ?? $metadata['span_id'] ?? null,
            'event_type' => class_basename($eventObj),
            'source' => $eventObj->source(),
            'category' => $eventObj->category(),
            'is_signal' => $eventObj->isSignal(),
            'trace_id' => $metadata['trace_id'] ?? null,
            'span_id' => $metadata['span_id'] ?? null,
            'parent_id' => $metadata['parent_id'] ?? null,
            'team_id' => $metadata['team_id'] ?? null,
            'actor_id' => $metadata['actor_id'] ?? null,
            'payload' => $eventObj->payload ?? [],
            'metadata' => $metadata,
            'occurred_at' => $eventObj->occurredAt ?? now(),
        ]);
    }

    protected function shouldPersist(DomainEventContract $event): bool
    {
        $category = $event->category();

        // 1. Business Signals: Always Persist
        if ($category === 'business') {
            return true;
        }

        // 2. Debug Mode Override (Check local config or cached setting)
        if (config('app.debug_events_enabled', false)) {
            return true;
        }

        // 3. Deterministic Sampling based on Trace ID
        // This ensures whole traces are kept/dropped together.
        $traceId = $event->metadata['trace_id'] ?? ($event->eventId ?? null);

        // CRC32 is fast and sufficient for uniform distribution logic
        $hash = crc32($traceId);
        $sampleBucket = abs($hash) % 100; // 0-99

        if ($category === 'operational') {
            // Keep 10%
            return $sampleBucket < 10;
        }

        if ($category === 'debug') {
            // Keep 1%
            return $sampleBucket < 1;
        }

        return true;
    }
}

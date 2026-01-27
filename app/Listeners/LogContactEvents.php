<?php

namespace App\Listeners;

use App\Events\ContactLifecycleChanged;
use App\Events\ContactOptedOut;
use App\Models\ContactEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class LogContactEvents implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event instanceof ContactLifecycleChanged) {
            $this->logLifecycleChange($event);
        } elseif ($event instanceof ContactOptedOut) {
            $this->logOptOut($event);
        }
    }

    protected function logLifecycleChange(ContactLifecycleChanged $event)
    {
        $contact = $event->contact;
        $idempotencyKey = "log_lifecycle:{$contact->id}:{$event->oldState}:{$event->newState}";

        // Prevent duplicate logging for the same transition strictly within a short window
        if (Cache::has($idempotencyKey)) {
            return;
        }

        ContactEvent::create([
            'team_id' => $contact->team_id,
            'contact_id' => $contact->id,
            'event_type' => 'LifecycleChanged',
            'event_data' => [
                'old_state' => $event->oldState,
                'new_state' => $event->newState,
            ],
            'occurred_at' => now(),
            'idempotency_key' => $idempotencyKey,
        ]);

        Cache::put($idempotencyKey, true, 60);
    }

    protected function logOptOut(ContactOptedOut $event)
    {
        $contact = $event->contact;

        $idempotencyKey = "log_optout:{$contact->id}:" . now()->timestamp;

        ContactEvent::create([
            'team_id' => $contact->team_id,
            'contact_id' => $contact->id,
            'event_type' => 'OptedOut',
            'event_data' => [
                'source' => $contact->opt_in_source ?? 'Unknown',
            ],
            'occurred_at' => now(),
            'idempotency_key' => $idempotencyKey,
        ]);
    }
}

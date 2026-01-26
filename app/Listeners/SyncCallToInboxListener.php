<?php

namespace App\Listeners;

use App\Events\CallEnded;
use App\Events\CallMissed;
use App\Events\CallRejected;
use App\Events\CallFailed;
use App\Services\CallLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncCallToInboxListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(protected CallLogService $callLogService)
    {
    }

    /**
     * Handle terminal call events.
     */
    public function handle(object $event): void
    {
        $call = $event->call;

        Log::info("Syncing call to inbox timeline", [
            'call_id' => $call->call_id,
            'event' => get_class($event),
        ]);

        $this->callLogService->logCall($call);

        // Record event for safeguards
        if (in_array($call->status, ['missed', 'failed', 'rejected'])) {
            $safeguardService = new \App\Services\CallSafeguardService();
            $safeguardService->recordEvent($call->team, $call->status);
        }
    }
}

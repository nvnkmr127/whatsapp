<?php

namespace App\Jobs;

use App\Models\Team;
use App\Services\TraceContext;
use App\Services\WhatsAppCallProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Processes a batch of WhatsApp call webhook events off the request path.
 *
 * Call events (connect/ringing/terminate) are status notifications, not a
 * synchronous request/response — running them inline in the webhook handler
 * risked timing out the webhook (and triggering Meta retries) on a slow batch.
 */
class ProcessWhatsAppCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [5, 30, 60];

    public function __construct(
        public int $teamId,
        public array $calls,
        public ?string $traceId = null,
    ) {
        $this->onQueue('high'); // drained first — call signalling is latency-sensitive
    }

    public function handle(): void
    {
        if ($this->traceId) {
            TraceContext::set($this->traceId);
        }

        $team = Team::find($this->teamId);
        if (! $team) {
            Log::warning("ProcessWhatsAppCallJob: Team {$this->teamId} not found, dropping call batch.");

            return;
        }

        (new WhatsAppCallProcessor)->process($team, $this->calls);
    }
}

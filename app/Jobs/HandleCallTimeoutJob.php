<?php

namespace App\Jobs;

use App\Models\WhatsAppCall;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HandleCallTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find calls stuck in 'initiated' or 'ringing' for more than 5 minutes
        $zombieCalls = WhatsAppCall::whereIn('status', ['initiated', 'ringing'])
            ->where('updated_at', '<', now()->subMinutes(5))
            ->get();

        foreach ($zombieCalls as $call) {
            Log::info("Auto-marking zombie call as missed/failed", [
                'call_id' => $call->call_id,
                'status' => $call->status,
                'initiated_at' => $call->initiated_at,
            ]);

            if ($call->status === 'ringing') {
                $call->markAsMissed();
                event(new \App\Events\CallMissed($call));
            } else {
                $call->markAsFailed('TIMEOUT');
                event(new \App\Events\CallFailed($call));
            }
        }
    }
}

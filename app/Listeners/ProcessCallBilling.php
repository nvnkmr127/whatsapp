<?php

namespace App\Listeners;

use App\Events\CallEnded;
use App\Services\BillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessCallBilling implements ShouldQueue
{
    use InteractsWithQueue;

    protected $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Handle the event.
     */
    public function handle(CallEnded $event): void
    {
        $call = $event->call;
        $team = $call->team;

        try {
            // Record call usage and deduct from wallet
            $success = $this->billingService->recordCallUsage($team, $call);

            if (!$success) {
                Log::warning("Call billing failed - insufficient balance", [
                    'team_id' => $team->id,
                    'call_id' => $call->call_id,
                    'cost' => $call->cost_amount,
                ]);

                // Optionally notify team about insufficient balance
                // You could dispatch a notification event here
            } else {
                Log::info("Call successfully billed", [
                    'team_id' => $team->id,
                    'call_id' => $call->call_id,
                    'cost' => $call->cost_amount,
                    'duration' => $call->duration_seconds,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error processing call billing", [
                'team_id' => $team->id,
                'call_id' => $call->call_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to retry the job
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(CallEnded $event, \Throwable $exception): void
    {
        Log::error("Call billing job failed permanently", [
            'call_id' => $event->call->call_id,
            'error' => $exception->getMessage(),
        ]);
    }
}

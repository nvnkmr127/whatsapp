<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Services\Integrations\IntegrationHealthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckIntegrationHealth implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(IntegrationHealthService $healthService): void
    {
        Log::info("Starting global integration health check...");

        $integrations = Integration::where('status', '!=', 'inactive')->get();

        foreach ($integrations as $integration) {
            try {
                $status = $healthService->checkHealth($integration);

                if ($status['state'] === 'broken') {
                    Log::error("Integration {$integration->id} ({$integration->name}) is BROKEN.");
                    // Trigger notifications if needed
                }
            } catch (\Exception $e) {
                Log::error("Failed to check health for integration {$integration->id}: " . $e->getMessage());
            }
        }
    }
}

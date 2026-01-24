<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Services\Integrations\MetaCommerceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProductsToMetaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $integrationId;

    /**
     * Create a new job instance.
     */
    public function __construct($integrationId)
    {
        $this->integrationId = $integrationId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $integration = Integration::find($this->integrationId);

        if (!$integration || $integration->type !== 'meta_commerce') {
            Log::error("SyncProductsToMetaJob: Invalid integration ID {$this->integrationId}");
            return;
        }

        try {
            $service = new MetaCommerceService($integration);
            $count = $service->syncProducts();
            Log::info("SyncProductsToMetaJob: Successfully synced {$count} products for team {$integration->team_id}");
        } catch (\Exception $e) {
            Log::error("SyncProductsToMetaJob: Failed for integration {$this->integrationId}. Error: " . $e->getMessage());
        }
    }
}

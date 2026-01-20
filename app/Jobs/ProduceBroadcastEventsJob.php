<?php

namespace App\Jobs;

use App\Models\CampaignSnapshot;
use App\Services\BroadcastEventProducer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProduceBroadcastEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $snapshotId;

    public function __construct($snapshotId)
    {
        $this->snapshotId = $snapshotId;
        $this->onQueue('broadcasts');
    }

    public function handle(BroadcastEventProducer $producer): void
    {
        $snapshot = CampaignSnapshot::find($this->snapshotId);

        if (!$snapshot) {
            Log::error("Snapshot {$this->snapshotId} not found for event production.");
            return;
        }

        Log::info("Starting event production for Snapshot {$this->snapshotId}");

        // Initialize tracking counter via Cache (driver agnostic)
        \Illuminate\Support\Facades\Cache::forever("campaign_total_count:{$snapshot->campaign_id}", $snapshot->audience_count);
        \Illuminate\Support\Facades\Cache::forever("campaign_processed_count:{$snapshot->campaign_id}", 0);

        $producer->produceEvents($snapshot);

        Log::info("Finished event production for Snapshot {$snapshot->id}");
    }
}

<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\BroadcastService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessCampaignJob implements ShouldQueue
{
    use Queueable;

    protected $campaignId;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function handle(BroadcastService $broadcastService): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (!$campaign) {
            return;
        }

        // Only process if scheduled or processing (resume)
        if (!in_array($campaign->status, ['scheduled', 'processing', 'draft'])) {
            Log::info("Campaign {$this->campaignId} is in status {$campaign->status}, skipping launch.");
            return;
        }

        Log::info("Launching Campaign {$this->campaignId} via ProcessCampaignJob wrapper.");

        $broadcastService->launch($campaign);
    }
}

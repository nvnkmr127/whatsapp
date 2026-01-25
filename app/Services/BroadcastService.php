<?php

namespace App\Services;

use App\Models\Campaign;
use App\Jobs\ProduceBroadcastEventsJob;
use Illuminate\Support\Facades\Log;

class BroadcastService
{
    protected $snapshotService;

    public function __construct(CampaignSnapshotService $snapshotService)
    {
        $this->snapshotService = $snapshotService;
    }

    /**
     * Launch a campaign by creating a snapshot and producing events.
     */
    public function launch(Campaign $campaign)
    {
        Log::info("Launching Event-Driven Campaign {$campaign->id}");

        // 0. Billing & Plan Enforcement
        if (!$campaign->team->canAccess('campaigns')) {
            $campaign->update(['status' => 'failed', 'error_message' => 'Campaigns feature not available on your plan.']);
            throw new \Exception("Campaign launch aborted: Entitlement failure for team {$campaign->team->id}");
        }

        if (!$campaign->team->canAccess('send_message')) {
            $campaign->update(['status' => 'failed', 'error_message' => 'Monthly message limit reached.']);
            throw new \Exception("Campaign launch aborted: Message limit reached for team {$campaign->team->id}");
        }

        // Commerce Readiness Check
        $template = $campaign->template;
        if ($template && in_array($template->category, ['UTILITY', 'TRANSACTIONAL'])) {
            $readinessService = app(\App\Services\CommerceReadinessService::class);
            if (!$readinessService->canPerformAction($campaign->team, 'broadcast')) {
                $campaign->update(['status' => 'failed', 'error_message' => 'Store not ready for commerce broadcasts.']);
                throw new \Exception("Campaign launch aborted: Commerce readiness failure for team {$campaign->team->id}");
            }
        }

        // 1. Create Snapshot (Immutable state for this run)
        $snapshot = $this->snapshotService->createSnapshot($campaign);

        // 2. Update Campaign Status
        $campaign->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        // 3. Dispatch Event Production (Async)
        ProduceBroadcastEventsJob::dispatch($snapshot->id);

        Log::info("Campaign {$campaign->id} transitioned to event production phase.");

        return $snapshot;
    }

    /**
     * Cancel a running campaign (Placeholder for event-based cancellation)
     */
    public function cancel(Campaign $campaign)
    {
        // In an event-driven system, we might place a "cancellation" flag in Redis 
        // that consumers check before processing an event from this campaign.
        $campaign->update(['status' => 'cancelled']);
        Log::info("Campaign {$campaign->id} marked as cancelled.");

        return true;
    }
}

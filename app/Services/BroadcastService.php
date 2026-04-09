<?php

namespace App\Services;

use App\Jobs\ProduceBroadcastEventsJob;
use App\Models\Campaign;
use Illuminate\Support\Facades\Log;

class BroadcastService
{
    protected $snapshotService;

    protected $healthMonitor;

    public function __construct(CampaignSnapshotService $snapshotService, WhatsAppHealthMonitor $healthMonitor)
    {
        $this->snapshotService = $snapshotService;
        $this->healthMonitor = $healthMonitor;
    }

    /**
     * Launch a campaign by creating a snapshot and producing events.
     */
    public function launch(Campaign $campaign)
    {
        Log::info("Launching Event-Driven Campaign {$campaign->id}");

        $expectedCount = $campaign->total_contacts ?? 0;

        if ($expectedCount === 0) {
            $campaign->update(['status' => 'failed', 'error_message' => 'Campaign has no contacts.']);

            return null;
        }

        // 0. Complete Billing & Plan Preflight Validation (BEFORE QUEUE DISPATCH)

        // Cache the message count for 5 minutes (300s) to avoid expensive DB scans on huge message tables
        $cacheKey = "team_msg_count_month:{$campaign->team_id}:".now()->format('Y-m');
        $messagesUsed = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($campaign) {
            return \App\Models\Message::where('team_id', $campaign->team_id)
                ->where('direction', 'outbound')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
        });

        // Calculate maximum potential cost (using unified config pricing)
        $category = $campaign->template ? strtolower($campaign->template->category) : 'marketing';
        $costPerMessage = config("whatsapp.pricing.{$category}", 0.05);
        $estimatedCost = $expectedCount * $costPerMessage;

        $preflight = app(\App\Services\OutboundPreflightService::class)->authorize(
            team: $campaign->team,
            feature: 'campaigns',
            limitKey: 'message_limit', // Check if current usage + campaign size blows past limit
            currentUsage: $messagesUsed + $expectedCount,
            cost: $estimatedCost
        );

        if (! $preflight['allowed']) {
            $campaign->update(['status' => 'failed', 'error_message' => $preflight['reason']]);
            throw new \Exception("Campaign launch aborted (PRE-QUEUE): {$preflight['reason']} [Code: {$preflight['code']}]");
        }

        // 0.1 WhatsApp Health Check (CRITICAL)
        $this->verifyHealth($campaign->team);

        // Removed globally restrictive Commerce Readiness Check for UTILITY/TRANSACTIONAL templates
        // as these are frequently used for non-commerce alerts (student fees, appointments, etc.)

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

    public function cancel(Campaign $campaign)
    {
        // In an event-driven system, we might place a "cancellation" flag in Redis
        // that consumers check before processing an event from this campaign.
        $campaign->update(['status' => 'cancelled']);
        Log::info("Campaign {$campaign->id} marked as cancelled.");

        return true;
    }

    protected function verifyHealth(\App\Models\Team $team)
    {
        // Check for specific blocking issues from Health Monitor
        $issues = $this->healthMonitor->getBlockingIssues($team);

        if (! empty($issues)) {
            $reason = implode(', ', $issues);
            Log::warning("Campaign Launch Blocked for Team {$team->id}: {$reason}");
            // Throwing exception here to be caught by Controller/Job
            throw new \Exception("Campaign launch aborted: WhatsApp Health Issues Detected ({$reason})");
        }
    }
}

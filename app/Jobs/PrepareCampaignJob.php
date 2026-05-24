<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PrepareCampaignJob implements ShouldQueue
{
    use \App\Traits\ChecksTenantMaintenanceMode;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaignId;

    public $criteria; // ['selection_type' => 'all' or 'ids' => [...]]

    /**
     * Create a new job instance.
     */
    public function __construct(int $campaignId, array $criteria)
    {
        $this->campaignId = $campaignId;
        $this->criteria = $criteria;
        $this->onQueue('campaigns');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = Campaign::find($this->campaignId);
        if (! $campaign) {
            return;
        }

        // ─────────────────────────────────────────────────────────────
        // MAINTENANCE GUARD: If the team is under backup/restore, discard
        // this job. PostRestoreStateResetService marks campaigns as 'interrupted'
        // so the admin can relaunch them intentionally after restore.
        // ─────────────────────────────────────────────────────────────
        if ($this->isTeamUnderMaintenance($campaign->team_id, 'delete')) {
            return;
        }

        $campaign->update(['status' => 'preparing']);

        try {
            $query = Contact::where('team_id', $campaign->team_id);

            // Filter logic
            if ($this->criteria['selection_type'] === 'tags' && ! empty($this->criteria['tags'])) {
                $query->whereHas('tags', function ($q) {
                    $q->whereIn('contact_tags.id', $this->criteria['tags']);
                });
            } elseif (in_array($this->criteria['selection_type'], ['contacts', 'ids']) && ! empty($this->criteria['ids'] ?? $this->criteria['contacts'] ?? [])) {
                $ids = $this->criteria['ids'] ?? $this->criteria['contacts'] ?? [];
                $query->whereIn('id', $ids);
            }

            // Chunking for performance
            $query->chunk(500, function ($contacts) use ($campaign) {
                $details = [];
                $timestamp = now();
                $isAB = $campaign->is_ab_test;
                $ratio = $campaign->split_ratio ?? 50;

                foreach ($contacts as $contact) {
                    $variant = 'A';
                    if ($isAB) {
                        $variant = (rand(1, 100) <= $ratio) ? 'A' : 'B';
                    }

                    $details[] = [
                        'campaign_id' => $campaign->id,
                        'contact_id' => $contact->id,
                        'phone' => $contact->phone_number,
                        'status' => 'pending',
                        'variant' => $variant,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
                CampaignDetail::insert($details);
            });

            // Update Counts
            $count = CampaignDetail::where('campaign_id', $campaign->id)->count();
            
            if ($campaign->campaign_type === 'drip') {
                $status = ($campaign->drip_trigger_type === 'tag_added') ? 'active' : 'processing';
            } else {
                $status = 'scheduled';
            }

            $campaign->update([
                'total_contacts' => $count,
                'status' => $status,
            ]);

            if ($campaign->campaign_type === 'drip') {
                CampaignDetail::where('campaign_id', $campaign->id)
                    ->chunk(500, function ($details) use ($campaign): void {
                        foreach ($details as $detail) {
                            \App\Jobs\ProcessDripStepJob::dispatch($campaign->id, $detail->contact_id, 0);
                        }
                    });
            }

            Log::info("Campaign {$campaign->id} prepared with {$count} contacts.");

        } catch (\Throwable $e) {
            Log::error("Failed to prepare campaign {$campaign->id}: ".$e->getMessage());
            $campaign->update(['status' => 'failed', 'error_message' => 'Preparation Failed: '.$e->getMessage()]);
            throw $e;
        }
    }
}

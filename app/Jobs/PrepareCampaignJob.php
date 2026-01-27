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
        if (!$campaign) {
            return;
        }

        $campaign->update(['status' => 'preparing']);

        try {
            $query = Contact::where('team_id', $campaign->team_id);

            // Filter logic
            if (($this->criteria['selection_type'] ?? 'ids') === 'ids') {
                if (!empty($this->criteria['ids'])) {
                    $query->whereIn('id', $this->criteria['ids']);
                }
            }

            // Chunking for performance
            $query->chunk(500, function ($contacts) use ($campaign) {
                $details = [];
                $timestamp = now();
                foreach ($contacts as $contact) {
                    $details[] = [
                        'campaign_id' => $campaign->id,
                        'rel_id' => $contact->id,
                        'rel_type' => 'contact',
                        'phone' => $contact->phone,
                        'status' => 'pending',
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
                CampaignDetail::insert($details);
            });

            // Update Counts
            $count = CampaignDetail::where('campaign_id', $campaign->id)->count();
            $campaign->update([
                'total_contacts' => $count,
                'status' => 'scheduled' // Ready for scheduler
            ]);

            Log::info("Campaign {$campaign->id} prepared with {$count} contacts.");

        } catch (\Throwable $e) {
            Log::error("Failed to prepare campaign {$campaign->id}: " . $e->getMessage());
            $campaign->update(['status' => 'failed', 'error_message' => 'Preparation Failed: ' . $e->getMessage()]);
            throw $e;
        }
    }
}

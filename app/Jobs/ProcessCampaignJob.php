<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCampaignJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $campaignId;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function handle(): void
    {
        $campaign = \App\Models\Campaign::find($this->campaignId);

        if (!$campaign || $campaign->status !== 'scheduled') {
            return;
        }

        $campaign->update(['status' => 'processing']);

        // Filter Logic
        $query = \App\Models\Contact::where('team_id', $campaign->team_id);
        $filters = $campaign->audience_filters ?? [];

        if (!empty($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('contact_tags.id', $filters['tags']);
            });
        } elseif (!empty($filters['contacts'])) {
            $query->whereIn('id', $filters['contacts']);
        } elseif (!empty($filters['all']) && $filters['all'] === true) {
            // target all
        } else {
            $query->whereRaw('1=0');
        }

        $total = $query->count();
        $campaign->update(['total_contacts' => $total]);

        if ($total === 0) {
            $campaign->update(['status' => 'completed']);
            return;
        }

        // Chunking
        $query->chunk(100, function ($contacts) use ($campaign) {
            foreach ($contacts as $contact) {
                dispatch(new \App\Jobs\SendCampaignMessageJob($campaign->id, $contact->id));
            }
        });

        // We mark as completed "dispatching", but actual sending is async. 
        // Monitor job? For now, just mark processed.
        $campaign->update(['status' => 'completed']);
    }
}

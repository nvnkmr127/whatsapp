<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;
use App\Jobs\SendCampaignMessage;
use Illuminate\Support\Facades\DB;

class BroadcastService
{
    /**
     * Launch a campaign immediately or schedule it.
     */
    public function launch(Campaign $campaign)
    {
        // 1. Resolve Contacts based on Segment
        $query = Contact::where('team_id', $campaign->team_id)
            ->where('opt_in_status', 'opted_in'); // SAFETY: Only opted-in users

        $config = $campaign->segment_config ?? [];

        if (isset($config['tags']) && is_array($config['tags']) && count($config['tags']) > 0) {
            $query->whereHas('tags', function ($q) use ($config) {
                $q->whereIn('contact_tags.id', $config['tags']);
            });
        }

        // Count total
        $total = $query->count();
        $campaign->update([
            'total_contacts' => $total,
            'status' => 'processing'
        ]);

        if ($total === 0) {
            $campaign->update(['status' => 'completed']);
            return;
        }

        // 2. Dispatch Jobs (Rate Limiting handled by Queue Worker or Job Middleware)
        // Chunking to avoid memory issues
        $query->chunk(100, function ($contacts) use ($campaign) {
            foreach ($contacts as $contact) {
                // Dispatch Job
                SendCampaignMessage::dispatch($campaign, $contact)
                    ->onQueue('whatsapp_broadcasts'); // Use a dedicated queue if possible
            }
        });
    }
}

<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignSnapshot;
use App\Models\CampaignSnapshotContact;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignSnapshotService
{
    /**
     * Create a snapshot for a campaign.
     */
    public function createSnapshot(Campaign $campaign): CampaignSnapshot
    {
        return DB::transaction(function () use ($campaign) {
            // 1. Resolve Audience
            $query = Contact::where('team_id', $campaign->team_id)
                ->where('opt_in_status', 'opted_in');

            $filters = $campaign->audience_filters ?? $campaign->segment_config ?? [];

            if (!empty($filters['tags'])) {
                $query->whereHas('tags', function ($q) use ($filters) {
                    $q->whereIn('contact_tags.id', $filters['tags']);
                });
            } elseif (!empty($filters['contacts'])) {
                $query->whereIn('id', $filters['contacts']);
            } elseif (!empty($filters['all']) && $filters['all'] === true) {
                // target all
            } else {
                if (empty($filters)) {
                    $query->whereRaw('1=0');
                }
            }

            $count = $query->count();

            // 2. Create Snapshot record
            $snapshot = CampaignSnapshot::create([
                'campaign_id' => $campaign->id,
                'template_name' => $campaign->template_name,
                'template_language' => $campaign->template_language ?? 'en_US',
                'template_variables' => $campaign->template_variables ?? [],
                'header_params' => $campaign->header_params ?? [],
                'footer_params' => $campaign->footer_params ?? [],
                'audience_count' => $count,
                'meta' => [
                    'filters' => $filters,
                    'created_at' => now(),
                ]
            ]);

            // 3. Populate Snapshot Contacts (Chunked to avoid memory issues)
            $query->chunk(1000, function ($contacts) use ($snapshot) {
                $batch = [];
                foreach ($contacts as $contact) {
                    $batch[] = [
                        'snapshot_id' => $snapshot->id,
                        'contact_id' => $contact->id,
                        'phone_number' => $contact->phone_number,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                CampaignSnapshotContact::insert($batch);
            });

            // 4. Update Campaign
            $campaign->update([
                'last_snapshot_id' => $snapshot->id,
                'total_contacts' => $count
            ]);

            Log::info("Created snapshot {$snapshot->id} for Campaign {$campaign->id} with {$count} contacts.");

            return $snapshot;
        });
    }
}

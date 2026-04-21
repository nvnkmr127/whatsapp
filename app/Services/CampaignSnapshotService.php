<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignSnapshot;
use App\Models\CampaignSnapshotContact;
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
            // 1. Resolve Audience from CampaignDetail (source of truth)
            $query = DB::table('campaign_details')
                ->join('contacts', 'campaign_details.contact_id', '=', 'contacts.id')
                ->where('campaign_details.campaign_id', $campaign->id)
                ->where('contacts.opt_in_status', 'opted_in')
                ->select('contacts.*', 'campaign_details.variant');

            $count = $query->count();

            // 2. Resolve Template Details (Fallback to relationship if direct fields are empty)
            $templateName = $campaign->template_name ?: ($campaign->template?->name);
            $templateLanguage = $campaign->template_language ?: ($campaign->template?->language ?? 'en_US');

            // 2. Create Snapshot record
            $snapshot = CampaignSnapshot::create([
                'campaign_id' => $campaign->id,
                'template_name' => $templateName,
                'template_language' => $templateLanguage,
                'template_variables' => $campaign->template_variables ?? [],
                'header_params' => $campaign->header_params ?? [],
                'footer_params' => $campaign->footer_params ?? [],
                'audience_count' => $count,
                'meta' => [
                    'filters' => $campaign->audience_filters ?? [],
                    'created_at' => now(),
                ],
            ]);

            // 3. Populate Snapshot Contacts (Chunked to avoid memory issues)
            $query->orderBy('contacts.id')->chunk(1000, function ($contacts) use ($snapshot) {
                $batch = [];
                foreach ($contacts as $contact) {
                    $batch[] = [
                        'snapshot_id' => $snapshot->id,
                        'contact_id' => $contact->id,
                        'phone_number' => $contact->phone_number,
                        'variant' => $contact->variant ?? 'A',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                CampaignSnapshotContact::insert($batch);
            });

            // 4. Update Campaign
            $campaign->update([
                'last_snapshot_id' => $snapshot->id,
                'total_contacts' => $count,
            ]);

            Log::info("Created snapshot {$snapshot->id} for Campaign {$campaign->id} with {$count} contacts.");

            return $snapshot;
        });
    }
}

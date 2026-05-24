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

class ProcessContactTagTriggerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $contactId;
    public $tagId;

    public function __construct(int $contactId, int $tagId)
    {
        $this->contactId = $contactId;
        $this->tagId = $tagId;
        $this->onQueue('campaigns');
    }

    public function handle(): void
    {
        $contact = Contact::find($this->contactId);
        if (!$contact) {
            return;
        }

        // Find campaigns that are active, are drip campaigns, and are triggered by tag addition
        $campaigns = Campaign::where('status', 'active')
            ->where('campaign_type', 'drip')
            ->where('drip_trigger_type', 'tag_added')
            ->get();

        foreach ($campaigns as $campaign) {
            $filters = $campaign->audience_filters;
            if (empty($filters)) {
                continue;
            }

            // Verify if the campaign filters match our tag and that the contact belongs to the same team
            if ($campaign->team_id !== $contact->team_id) {
                continue;
            }

            if (($filters['type'] ?? '') === 'tags' && in_array($this->tagId, $filters['tags'] ?? [])) {
                // Check if the contact has already received this campaign (or is currently receiving it)
                $alreadyExists = CampaignDetail::where('campaign_id', $campaign->id)
                    ->where('contact_id', $this->contactId)
                    ->exists();

                if (!$alreadyExists) {
                    CampaignDetail::create([
                        'campaign_id' => $campaign->id,
                        'contact_id' => $this->contactId,
                        'phone' => $contact->phone_number,
                        'status' => 'pending',
                        'variant' => 'A',
                    ]);

                    $campaign->increment('total_contacts');

                    \App\Jobs\ProcessDripStepJob::dispatch($campaign->id, $this->contactId, 0);

                    Log::info("Dynamic Drip Campaign triggered", [
                        'campaign_id' => $campaign->id,
                        'contact_id' => $this->contactId,
                        'tag_id' => $this->tagId,
                    ]);
                }
            }
        }
    }
}

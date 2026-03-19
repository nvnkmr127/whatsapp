<?php

namespace App\Services;

use App\Models\CampaignSnapshot;
use Illuminate\Support\Facades\Log;

class BroadcastEventProducer
{
    public function __construct(protected EventBusService $eventBus)
    {
    }

    /**
     * Produce message.queued events for all contacts in a snapshot in efficient batches.
     */
    public function produceEvents(CampaignSnapshot $snapshot): void
    {
        $snapshot->contacts()->chunk(500, function ($contacts) use ($snapshot) {
            $events = [];

            foreach ($contacts as $contact) {
                $events[] = [
                    'team_id' => $snapshot->campaign->team_id,
                    'event_type' => 'message.queued',
                    'payload' => [
                        'campaign_id' => $snapshot->campaign_id,
                        'snapshot_id' => $snapshot->id,
                        'contact_id' => $contact->contact_id,
                        'phone_number' => $contact->phone_number,
                        'meta' => array_merge($snapshot->meta ?? [], [
                            'team_id' => $snapshot->campaign->team_id,
                        ]),
                        'template' => [
                            'name' => $snapshot->template_name,
                            'language' => $snapshot->template_language,
                            'variables' => $snapshot->template_variables,
                            'header_params' => $snapshot->header_params,
                            'footer_params' => $snapshot->footer_params,
                        ],
                    ]
                ];
            }

            $this->eventBus->publishBatch('whatsapp_broadcasts', $events);
        });

        Log::info("Produced events for Campaign Snapshot {$snapshot->id}");
    }
}

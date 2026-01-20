<?php

namespace App\Services;

use App\Models\CampaignSnapshot;
use App\Services\EventBusService;
use Illuminate\Support\Facades\Log;

class BroadcastEventProducer
{
    protected $eventBus;

    public function __construct(EventBusService $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    /**
     * Produce message.queued events for all contacts in a snapshot.
     */
    public function produceEvents(CampaignSnapshot $snapshot): void
    {
        $snapshot->contacts()->chunk(500, function ($contacts) use ($snapshot) {
            foreach ($contacts as $contact) {
                $payload = [
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
                ];

                $this->eventBus->publish(
                    'whatsapp_broadcasts',
                    'message.queued',
                    $payload
                );
            }
        });

        Log::info("Produced events for Campaign Snapshot {$snapshot->id}");
    }
}

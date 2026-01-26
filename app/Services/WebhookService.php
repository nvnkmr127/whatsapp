<?php

namespace App\Services;

use App\Models\WebhookSubscription;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch a webhook event to all subscribed endpoints.
     */
    public function dispatch(?int $teamId, string $eventType, array $data): void
    {
        $query = WebhookSubscription::where('is_active', true);

        if ($teamId) {
            $query->where('team_id', $teamId);
        } else {
            // System-wide webhooks
            $query->where('is_system', true);
        }

        $subscriptions = $query->get();

        foreach ($subscriptions as $subscription) {
            if ($subscription->isSubscribedTo($eventType)) {
                $this->sendWebhook($subscription, $eventType, $data);
            }
        }
    }

    /**
     * Send a webhook to a specific subscription.
     */
    protected function sendWebhook(WebhookSubscription $subscription, string $eventType, array $data): void
    {
        \App\Jobs\ExecuteOutboundWebhookJob::dispatch(
            $subscription->id,
            $eventType,
            $data
        );
    }
}

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
        $payload = [
            'event' => $eventType,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];

        $signature = null;
        if ($subscription->secret) {
            $signature = $this->generateSignature($payload, $subscription->secret);
        }

        $attemptedAt = now();

        try {
            $response = Http::timeout(10)
                ->withHeaders($signature ? ['X-Webhook-Signature' => $signature] : [])
                ->post($subscription->url, $payload);

            // Log delivery
            WebhookDelivery::create([
                'webhook_subscription_id' => $subscription->id,
                'event_type' => $eventType,
                'payload' => $payload,
                'status_code' => $response->status(),
                'response' => $response->body(),
                'attempted_at' => $attemptedAt,
            ]);

            if ($response->failed()) {
                Log::warning("Webhook delivery failed for subscription {$subscription->id}: HTTP {$response->status()}");
            }

        } catch (\Exception $e) {
            // Log failed delivery
            WebhookDelivery::create([
                'webhook_subscription_id' => $subscription->id,
                'event_type' => $eventType,
                'payload' => $payload,
                'status_code' => null,
                'response' => $e->getMessage(),
                'attempted_at' => $attemptedAt,
            ]);

            Log::error("Webhook delivery exception for subscription {$subscription->id}: " . $e->getMessage());
        }
    }

    /**
     * Generate HMAC-SHA256 signature for payload.
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        $jsonPayload = json_encode($payload);
        return 'sha256=' . hash_hmac('sha256', $jsonPayload, $secret);
    }
}

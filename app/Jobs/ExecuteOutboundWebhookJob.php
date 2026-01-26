<?php

namespace App\Jobs;

use App\Models\WebhookSubscription;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExecuteOutboundWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $subscriptionId;
    public $eventType;
    public $data;
    public $tries = 3;
    public $backoff = [10, 60, 300];

    public function __construct(int $subscriptionId, string $eventType, array $data)
    {
        $this->subscriptionId = $subscriptionId;
        $this->eventType = $eventType;
        $this->data = $data;
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $subscription = WebhookSubscription::find($this->subscriptionId);
        if (!$subscription || !$subscription->is_active) {
            return;
        }

        $payload = [
            'event' => $this->eventType,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->data,
        ];

        $signature = null;
        if ($subscription->secret) {
            $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $subscription->secret);
        }

        $attemptedAt = now();

        try {
            $response = Http::timeout(10)
                ->withHeaders($signature ? ['X-Webhook-Signature' => $signature] : [])
                ->post($subscription->url, $payload);

            WebhookDelivery::create([
                'webhook_subscription_id' => $subscription->id,
                'event_type' => $this->eventType,
                'payload' => $payload,
                'status_code' => $response->status(),
                'response' => $response->body(),
                'attempted_at' => $attemptedAt,
            ]);

            if ($response->failed() && $this->attempts() < $this->tries) {
                throw new \Exception("Webhook failed with status: " . $response->status());
            }

        } catch (\Exception $e) {
            WebhookDelivery::create([
                'webhook_subscription_id' => $subscription->id,
                'event_type' => $this->eventType,
                'payload' => $payload,
                'status_code' => null,
                'response' => $e->getMessage(),
                'attempted_at' => $attemptedAt,
            ]);

            Log::error("Webhook delivery failed for subscription {$subscription->id}: " . $e->getMessage());

            throw $e;
        }
    }
}

<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Models\WebhookSubscription;
use App\Services\Mcp\McpTool;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TriggerWebhookTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'trigger_webhook',
            'description' => 'Fire an outbound webhook subscription that is registered under this tenant. Useful for notifying external systems.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'webhook_id' => ['type' => 'integer', 'description' => 'ID of the WebhookSubscription to trigger'],
                    'event'      => ['type' => 'string', 'description' => 'Event name (e.g. mcp.trigger)'],
                    'payload'    => [
                        'type'        => 'object',
                        'description' => 'JSON payload to send to the webhook URL',
                        'default'     => [],
                    ],
                ],
                'required' => ['webhook_id', 'event'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $subscription = WebhookSubscription::where('id', $input['webhook_id'])
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->firstOrFail();

        $event   = $input['event'];
        $payload = array_merge($input['payload'] ?? [], [
            'event'      => $event,
            'team_id'    => $team->id,
            'timestamp'  => now()->toIso8601String(),
            'source'     => 'mcp',
        ]);

        // Sign the payload if a secret is configured — same as existing WebhookService pattern
        $headers = ['Content-Type' => 'application/json'];
        if ($subscription->secret) {
            $headers['X-Webhook-Signature'] = 'sha256='.hash_hmac('sha256', json_encode($payload), $subscription->secret);
        }

        $response = Http::withHeaders($headers)->post($subscription->url, $payload);

        Log::info('MCP webhook triggered', [
            'subscription_id' => $subscription->id,
            'event'           => $event,
            'status'          => $response->status(),
        ]);

        if (! $response->successful()) {
            throw new \Exception("Webhook delivery failed with HTTP {$response->status()}.");
        }

        return [['type' => 'text', 'text' => "Webhook fired to {$subscription->url}. HTTP {$response->status()}."]];
    }
}

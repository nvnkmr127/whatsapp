<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InboundWebhookController extends Controller
{
    /**
     * Receive webhooks from external software.
     * POST /api/v1/webhooks/inbound
     */
    public function handle(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (!$team) {
            return response()->json(['error' => 'No team context'], 400);
        }

        // Log the incoming webhook
        Log::info('Inbound webhook received', [
            'team_id' => $team->id,
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        // Store the webhook payload for processing
        try {
            \App\Models\WebhookPayload::create([
                'payload' => $request->all(),
                'signature' => $request->header('X-Webhook-Signature'),
                'status' => 'pending',
                'waba_id' => $team->whatsapp_business_account_id,
            ]);

            // You can dispatch a job here to process the webhook
            // \App\Jobs\ProcessInboundWebhookJob::dispatch($payload);

            return response()->json([
                'success' => true,
                'message' => 'Webhook received and queued for processing',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to store inbound webhook', [
                'error' => $e->getMessage(),
                'team_id' => $team->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process webhook',
            ], 500);
        }
    }

    /**
     * Get inbound webhook URL for the team.
     * GET /api/v1/webhooks/inbound/url
     */
    public function getUrl(Request $request)
    {
        $url = url('/api/v1/webhooks/inbound');

        return response()->json([
            'success' => true,
            'url' => $url,
            'instructions' => [
                'method' => 'POST',
                'authentication' => 'Bearer token (required)',
                'content_type' => 'application/json',
                'example' => [
                    'event' => 'order.created',
                    'data' => [
                        'order_id' => '12345',
                        'customer_phone' => '+1234567890',
                        'total' => 99.99,
                    ],
                ],
            ],
        ]);
    }
}

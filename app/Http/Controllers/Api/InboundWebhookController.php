<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMappedWebhookJob;
use App\Models\WebhookPayload;
use App\Models\WebhookSource;
use App\Services\WebhookAuthService;
use App\Services\WebhookMappingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InboundWebhookController extends Controller
{
    public function __construct(
        protected WebhookAuthService $authService,
        protected WebhookMappingService $mappingService
    ) {
    }

    /**
     * Handle webhook from a specific source
     * POST /api/v1/webhooks/inbound/{source}
     */
    public function handleSource(Request $request, string $sourceSlug)
    {
        // Find webhook source
        $source = WebhookSource::where('slug', $sourceSlug)->first();

        if (!$source) {
            Log::warning('Webhook source not found', ['slug' => $sourceSlug]);
            return response()->json(['error' => 'Webhook source not found'], 404);
        }

        if (!$source->is_active) {
            Log::warning('Webhook source is inactive', ['slug' => $sourceSlug]);
            return response()->json(['error' => 'Webhook source is inactive'], 403);
        }

        // Increment received counter
        $source->incrementReceived();

        // Authenticate webhook
        if (!$this->authService->verify($request, $source->auth_method, $source->getAuthConfig())) {
            Log::warning('Webhook authentication failed', [
                'source_id' => $source->id,
                'source_name' => $source->name,
                'auth_method' => $source->auth_method,
                'url' => $request->fullUrl(),
                'headers' => array_keys($request->headers->all()),
            ]);
            return response()->json([
                'error' => 'Authentication failed',
                'required_auth' => $source->auth_method,
                'tip' => 'Check your webhook source configuration for the correct credentials and headers.'
            ], 401);
        }

        // Get payload
        $payload = $request->all();

        // Extract event type
        $eventType = $this->mappingService->extractEventType(
            $payload,
            config("webhook-platforms.{$source->platform}.event_type_path")
        );

        Log::info('Inbound webhook received', [
            'source' => $source->name,
            'event_type' => $eventType,
            'payload_size' => strlen(json_encode($payload)),
        ]);

        // Check if payload passes filtering rules
        if (!$source->checkFilters($payload)) {
            Log::info('Webhook skipped by filters', ['source' => $source->name]);

            WebhookPayload::create([
                'webhook_source_id' => $source->id,
                'payload' => $payload,
                'event_type' => $eventType,
                'signature' => $request->header('X-Webhook-Signature')
                    ?? $request->header('X-Shopify-Hmac-SHA256')
                    ?? $request->header('Stripe-Signature')
                    ?? $request->header('X-WC-Webhook-Signature'),
                'status' => 'skipped',
                'waba_id' => $source->team->whatsapp_business_account_id ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook skipped by filtering rules',
                'event_type' => $eventType,
            ], 200);
        }

        try {
            // Get field mappings for this event type
            $fieldMappings = $source->getFieldMapping($eventType);

            // Map fields
            $mappedData = [];
            if (!empty($fieldMappings)) {
                $mappedData = $this->mappingService->mapFields($payload, $fieldMappings);

                // Apply transformations
                $transformationRules = $source->getTransformationRules();
                if (!empty($transformationRules)) {
                    $mappedData = $this->mappingService->transformData($mappedData, $transformationRules);
                }
            }

            // Store webhook payload
            $webhookPayload = WebhookPayload::create([
                'webhook_source_id' => $source->id,
                'payload' => $payload,
                'mapped_data' => $mappedData,
                'event_type' => $eventType,
                'signature' => $request->header('X-Webhook-Signature')
                    ?? $request->header('X-Shopify-Hmac-SHA256')
                    ?? $request->header('Stripe-Signature')
                    ?? $request->header('X-WC-Webhook-Signature'),
                'status' => 'pending',
                'waba_id' => $source->team->whatsapp_business_account_id ?? null,
            ]);

            // Execute action if configured
            $actionConfig = $source->getActionConfig();
            if (!empty($actionConfig) && !empty($mappedData)) {
                // Validate mapped data has required fields
                if ($this->mappingService->validateMappedData($mappedData)) {
                    $delay = $source->process_delay > 0 ? now()->addMinutes($source->process_delay) : null;
                    ProcessMappedWebhookJob::dispatch($webhookPayload, $actionConfig)->delay($delay);
                } else {
                    Log::warning('Webhook mapped data validation failed', [
                        'source' => $source->name,
                        'mapped_data' => $mappedData,
                    ]);
                    $webhookPayload->update([
                        'status' => 'failed',
                        'error_message' => 'Mapped data validation failed',
                    ]);
                }
            } else {
                // No action configured, just mark as processed
                $webhookPayload->update(['status' => 'processed']);
                $source->incrementProcessed();
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook received and queued for processing',
                'event_type' => $eventType,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to process inbound webhook', [
                'error' => $e->getMessage(),
                'source' => $source->name,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process webhook',
            ], 500);
        }
    }

    /**
     * Receive webhooks from external software (legacy endpoint)
     * POST /api/v1/webhooks/inbound
     */
    public function handle(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (!$team) {
            return response()->json(['error' => 'No team context'], 400);
        }

        // Log the incoming webhook
        Log::info('Inbound webhook received (legacy)', [
            'team_id' => $team->id,
            'user_id' => $request->user()?->id,
            'payload' => $request->all(),
            'headers' => array_keys($request->headers->all()), // Log keys only for privacy
        ]);

        // Store the webhook payload for processing
        try {
            \App\Models\WebhookPayload::create([
                'payload' => $request->all(),
                'signature' => $request->header('X-Webhook-Signature'),
                'status' => 'pending',
                'waba_id' => $team->whatsapp_business_account_id,
            ]);

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
     * Get inbound webhook URL for a source
     * GET /api/v1/webhooks/sources/{source}/url
     */
    public function getSourceUrl(Request $request, string $sourceSlug)
    {
        $source = WebhookSource::where('slug', $sourceSlug)
            ->where('team_id', $request->user()->currentTeam->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'url' => $source->getWebhookUrl(),
            'source' => [
                'name' => $source->name,
                'platform' => $source->platform,
                'auth_method' => $source->auth_method,
            ],
            'instructions' => [
                'method' => 'POST',
                'content_type' => 'application/json',
                'authentication' => $this->getAuthInstructions($source),
            ],
        ]);
    }

    /**
     * Get inbound webhook URL for the team (legacy)
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

    protected function getAuthInstructions(WebhookSource $source): array
    {
        return match ($source->auth_method) {
            'hmac' => [
                'type' => 'HMAC Signature',
                'header' => $source->getAuthConfig('header', 'X-Webhook-Signature'),
                'note' => 'Include HMAC signature in the specified header',
            ],
            'api_key' => [
                'type' => 'API Key',
                'header' => $source->getAuthConfig('header', 'X-API-Key'),
                'note' => 'Include API key in the specified header',
            ],
            'basic' => [
                'type' => 'Basic Authentication',
                'header' => 'Authorization',
                'note' => 'Use Basic Auth with configured credentials',
            ],
            'none' => [
                'type' => 'None',
                'note' => 'No authentication required',
            ],
            default => [],
        };
    }
}

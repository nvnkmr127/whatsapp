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
    use \App\Traits\StandardApiResponses;

    public function __construct(
        protected WebhookAuthService $authService,
        protected WebhookMappingService $mappingService
    ) {}

    /**
     * Handle webhook from a specific source
     * POST /api/v1/webhooks/inbound/{source}
     */
    public function handleSource(Request $request, string $sourceSlug)
    {
        // Find webhook source
        $source = WebhookSource::where('slug', $sourceSlug)->first();

        if (! $source) {
            Log::warning('Webhook source not found', ['slug' => $sourceSlug]);

            // Return slug in error to help user debug typos
            return $this->error("Webhook source not found for slug: {$sourceSlug}", 404, null, 'ERR_WEBHOOK_SOURCE_NOT_FOUND');
        }

        if (! $source->is_active) {
            Log::warning('Webhook source is inactive', ['slug' => $sourceSlug]);

            return $this->error('Webhook source is inactive', 403, null, 'ERR_WEBHOOK_SOURCE_INACTIVE');
        }

        // Increment received counter
        $source->incrementReceived();

        // Authenticate webhook
        if (! $this->authService->verify($request, $source->auth_method, $source->getAuthConfig())) {
            Log::warning('Webhook authentication failed', [
                'source_id' => $source->id,
                'source_name' => $source->name,
                'auth_method' => $source->auth_method,
                'url' => $request->fullUrl(),
                'headers' => array_keys($request->headers->all()),
            ]);

            return $this->error('Authentication failed', 401, [
                'required_auth' => $source->auth_method,
                'tip' => 'Check your webhook source configuration for the correct credentials and headers.',
            ], 'ERR_WEBHOOK_AUTH_FAILED');
        }

        // Get payload
        $payload = $request->all();

        // Extract External ID and Generate Hash for Deduplication
        $externalId = $this->mappingService->extractExternalId($payload, $request->headers->all(), $source->platform);
        $dedupeHash = $this->mappingService->generateDeduplicationHash($payload);

        // Extract Event Type
        $eventType = $this->mappingService->extractEventType(
            $payload,
            config("webhook-platforms.{$source->platform}.event_type_path")
        ) ?? 'webhook.received';

        $signature = $request->header('X-Webhook-Signature')
            ?? $request->header('X-Shopify-Hmac-SHA256')
            ?? $request->header('Stripe-Signature')
            ?? $request->header('X-WC-Webhook-Signature');

        // Deduplication Check: Look for existing payload from same source with same ID or Hash in last 24h
        $duplicate = WebhookPayload::where('webhook_source_id', $source->id)
            ->where(function ($query) use ($externalId, $dedupeHash) {
                if ($externalId) {
                    $query->where('external_id', $externalId);
                } else {
                    $query->where('deduplication_hash', $dedupeHash);
                }
            })
            ->where('created_at', '>', now()->subHours(24))
            ->whereIn('status', ['pending', 'processing', 'processed'])
            ->first();

        if ($duplicate) {
            // Store duplicate for visibility in analytics
            WebhookPayload::create([
                'webhook_source_id' => $source->id,
                'payload' => $payload,
                'event_type' => $eventType,
                'external_id' => $externalId,
                'deduplication_hash' => $dedupeHash,
                'signature' => $signature,
                'status' => 'duplicate',
                'team_id' => $source->team_id,
                'waba_id' => $source->team->whatsapp_business_account_id ?? null,
            ]);

            Log::info('Duplicate inbound webhook stored', [
                'source' => $source->name,
                'external_id' => $externalId,
                'status' => 'duplicate',
            ]);

            return $this->success([
                'duplicate_id' => $duplicate->id,
            ], 'Duplicate webhook recorded (already processed)');
        }

        Log::info('Inbound webhook received', [
            'source' => $source->name,
            'event_type' => $eventType,
            'external_id' => $externalId,
            'payload_size' => strlen(json_encode($payload)),
        ]);

        // Check if payload passes filtering rules
        if (! $source->checkFilters($payload)) {
            Log::info('Webhook skipped by filters', ['source' => $source->name]);

            WebhookPayload::create([
                'webhook_source_id' => $source->id,
                'payload' => $payload,
                'event_type' => $eventType,
                'external_id' => $externalId,
                'deduplication_hash' => $dedupeHash,
                'signature' => $request->header('X-Webhook-Signature')
                    ?? $request->header('X-Shopify-Hmac-SHA256')
                    ?? $request->header('Stripe-Signature')
                    ?? $request->header('X-WC-Webhook-Signature'),
                'status' => 'skipped',
                'team_id' => $source->team_id,
                'waba_id' => $source->team->whatsapp_business_account_id ?? null,
            ]);

            return $this->success([
                'event_type' => $eventType,
            ], 'Webhook skipped by filtering rules');
        }

        try {
            // Get field mappings for this event type
            $fieldMappings = $source->getFieldMapping($eventType);

            // Map fields
            $mappedData = [];
            if (! empty($fieldMappings)) {
                $mappedData = $this->mappingService->mapFields($payload, $fieldMappings);

                // Apply transformations
                $transformationRules = $source->getTransformationRules();
                if (! empty($transformationRules)) {
                    $mappedData = $this->mappingService->transformData($mappedData, $transformationRules);
                }
            }

            // Store webhook payload
            $webhookPayload = WebhookPayload::create([
                'webhook_source_id' => $source->id,
                'payload' => $payload,
                'mapped_data' => $mappedData,
                'event_type' => $eventType,
                'external_id' => $externalId,
                'deduplication_hash' => $dedupeHash,
                'signature' => $request->header('X-Webhook-Signature')
                    ?? $request->header('X-Shopify-Hmac-SHA256')
                    ?? $request->header('Stripe-Signature')
                    ?? $request->header('X-WC-Webhook-Signature'),
                'status' => 'pending',
                'team_id' => $source->team_id,
                'waba_id' => $source->team->whatsapp_business_account_id ?? null,
            ]);

            // Execute action if configured
            // Execute action if configured
            $actionConfig = $source->getActionConfig();

            if (! empty($actionConfig)) {
                if (empty($mappedData)) {
                    // Action configured but no data mapped - Likely a mapping error
                    Log::warning('Webhook processed but no data mapped for action', [
                        'source' => $source->name,
                        'payload_id' => $webhookPayload->id,
                    ]);

                    $webhookPayload->update([
                        'status' => 'failed',
                        'error_message' => 'Action configured but no fields were mapped. Check your field mapping configuration.',
                    ]);
                    $source->incrementFailed();
                } else {
                    // Validate mapped data has required fields
                    if ($this->mappingService->validateMappedData($mappedData)) {
                        $delay = $source->process_delay > 0 ? now()->addMinutes($source->process_delay) : null;
                        $traceId = \App\Services\TraceContext::getTraceId();
                        ProcessMappedWebhookJob::dispatch($webhookPayload, $actionConfig, $traceId)->delay($delay);
                    } else {
                        Log::warning('Webhook mapped data validation failed', [
                            'source' => $source->name,
                            'mapped_data' => $mappedData,
                        ]);
                        $webhookPayload->update([
                            'status' => 'failed',
                            'error_message' => 'Mapped data validation failed. Ensure phone_number is present and in a valid format (+country_code number).',
                        ]);
                        $source->incrementFailed();
                    }
                }
            } else {
                // No action configured, just mark as processed
                $webhookPayload->update(['status' => 'processed']);
                $source->incrementProcessed();
            }

            // T12: Automation Trigger for webhook payload received
            try {
                $contactPhone = $mappedData['phone_number'] ?? $payload['customer']['phone'] ?? $payload['phone'] ?? null;
                if ($contactPhone) {
                    $contact = \App\Models\Contact::where('phone_number', $contactPhone)->first();
                    if ($contact) {
                        app(\App\Services\AutomationService::class)->checkSpecialTriggers($contact, 'webhook_payload_received', array_merge([
                            'source_id' => $source->id,
                            'source_slug' => $source->slug,
                            'event_type' => $eventType,
                        ], $mappedData ?? [], $payload ?? []));
                    }
                }
            } catch (\Exception $e) {
                Log::debug('T12 Trigger failed (Ignored): '.$e->getMessage());
            }

            return $this->success([
                'event_type' => $eventType,
            ], 'Webhook received and queued for processing');

        } catch (\Exception $e) {
            Log::error('Failed to process inbound webhook', [
                'error' => $e->getMessage(),
                'source' => $source->name,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Failed to process webhook', 500, null, 'ERR_WEBHOOK_INTERNAL_ERROR');
        }
    }

    /**
     * Receive webhooks from external software (legacy endpoint)
     * POST /api/v1/webhooks/inbound
     */
    public function handle(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (! $team) {
            return $this->error('No team context selected.', 401, null, 'ERR_AUTH_NO_TEAM');
        }

        // Log the incoming webhook
        Log::info('Inbound webhook received (legacy)', [
            'team_id' => $team->id,
            'user_id' => $request->user()?->id,
            'payload_size' => strlen($request->getContent()),
        ]);

        $payload = $request->all();
        $eventType = $this->mappingService->extractEventType($payload) ?? 'webhook.received';

        // Store the webhook payload for processing
        try {
            \App\Models\WebhookPayload::create([
                'payload' => $payload,
                'event_type' => $eventType,
                'signature' => $request->header('X-Webhook-Signature'),
                'status' => 'pending',
                'team_id' => $team->id,
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
        $team = $request->user()->currentTeam;
        if (! $team) {
            return response()->json([
                'success' => false,
                'error' => 'No team context',
            ], 400);
        }

        $source = WebhookSource::where('slug', $sourceSlug)
            ->where('team_id', $team->id)
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

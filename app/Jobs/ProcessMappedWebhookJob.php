<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Message;
use App\Models\WebhookPayload;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessMappedWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public WebhookPayload $payload,
        public array $actionConfig,
        public ?string $traceId = null
    ) {}

    public function handle(): void
    {
        // 1. Restore Trace Context
        if ($this->traceId) {
            \App\Services\TraceContext::set($this->traceId);
        } else {
            \App\Services\TraceContext::ensureTraceId();
        }

        $actionType = $this->actionConfig['type'] ?? null;

        try {
            $this->syncContactTag();

            match ($actionType) {
                'send_template' => $this->sendTemplate(),
                'send_otp' => $this->sendOtp(),
                'send_media' => $this->sendMedia(),
                'upsert_contact' => $this->upsertContact(),
                'start_automation' => $this->startAutomation(),
                'forward_webhook' => $this->forwardWebhook(),
                'multiple' => $this->executeMultipleActions(),
                default => Log::warning('Unknown webhook action type', ['type' => $actionType]),
            };

            $this->payload->update([
                'status' => 'processed',
                'error_message' => null,
                'next_retry_at' => null,
            ]);
            $this->payload->source?->incrementProcessed();
        } catch (\Exception $e) {
            $source = $this->payload->source;
            $retryConfig = $source?->retry_config ?? [];
            $retryEnabled = $retryConfig['enabled'] ?? false;
            $maxRetries = (int) ($retryConfig['max_retries'] ?? 3);
            $currentRetryCount = (int) ($this->payload->retry_count ?? 0);

            Log::error('Failed to process webhook action', [
                'action_type' => $actionType,
                'error' => $e->getMessage(),
                'payload_id' => $this->payload->id,
                'retry_count' => $currentRetryCount,
                'retry_enabled' => $retryEnabled,
            ]);

            // Handle Retries
            if ($retryEnabled && $currentRetryCount < $maxRetries) {
                $newRetryCount = $currentRetryCount + 1;
                $interval = (int) ($retryConfig['retry_interval'] ?? 60);
                $strategy = $retryConfig['retry_strategy'] ?? 'exponential';

                // Calculate delay
                $delaySeconds = ($strategy === 'exponential')
                    ? $interval * pow(2, $currentRetryCount) // 0->1x, 1->2x, 2->4x, 3->8x...
                    : $interval;

                $nextRetryAt = now()->addSeconds($delaySeconds);

                $this->payload->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'last_error' => $e->getMessage()."\n".$e->getTraceAsString(),
                    'retry_count' => $newRetryCount,
                    'next_retry_at' => $nextRetryAt,
                ]);

                // Re-dispatch with delay
                self::dispatch($this->payload, $this->actionConfig, $this->traceId)
                    ->delay($nextRetryAt);

                Log::info('Webhook job rescheduled for retry', [
                    'payload_id' => $this->payload->id,
                    'retry_count' => $newRetryCount,
                    'next_retry_at' => $nextRetryAt->toDateTimeString(),
                ]);
            } else {
                // Final failure
                $this->payload->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'last_error' => $e->getMessage()."\n".$e->getTraceAsString(),
                    'next_retry_at' => null,
                ]);

                if ($source) {
                    $source->incrementFailed();
                }
            }

            // Still throw if no more retries to let the queue manager know if it's NOT a custom retry
            // But if we ARE handling retries manually, we might not want it to fail in the queue immediately?
            // Actually, re-dispatching manually is cleaner for visibility in our `webhook_payloads` table.
        }
    }

    /**
     * Send a media message (PDF, Image, etc.)
     */
    protected function sendMedia(): void
    {
        $whatsappService = app(WhatsAppService::class)->setTeam($this->payload->source->team);
        
        $mediaType = $this->actionConfig['media_type'] ?? 'document';
        $urlField = $this->actionConfig['media_url_field'] ?? 'media_url';
        $phoneField = $this->actionConfig['phone_field'] ?? 'phone_number';
        $captionField = $this->actionConfig['caption_field'] ?? null;
        
        $to = $this->payload->mapped_data[$phoneField] ?? null;
        $url = $this->payload->mapped_data[$urlField] ?? null;
        $caption = $captionField ? ($this->payload->mapped_data[$captionField] ?? null) : null;

        if (!$to || !$url) {
            throw new \Exception("Missing required fields for send_media (Phone: {$to}, URL: {$url})");
        }

        $response = $whatsappService->sendMedia($to, $mediaType, $url, $caption);

        if (!($response['success'] ?? false)) {
            throw new \Exception('Failed to send media via WhatsApp: '.json_encode($response['error'] ?? 'Unknown error'));
        }

        Log::info('Webhook triggered media send', [
            'to' => $to,
            'type' => $mediaType,
            'url' => $url
        ]);
    }

    protected function sendTemplate(): void
    {
        $templateId = $this->actionConfig['template_id'] ?? null;
        if (is_array($templateId)) {
            $templateId = reset($templateId); // Handle case where ID came as array
        }

        $parameterMapping = $this->normalizeMapping($this->actionConfig['parameter_mapping'] ?? []);
        $phoneField = $this->actionConfig['phone_field'] ?? 'phone_number';

        if (! $templateId) {
            throw new \Exception('Template ID not configured');
        }

        $template = WhatsappTemplate::find($templateId);
        if (! $template || $template instanceof \Illuminate\Database\Eloquent\Collection) {
            // Ensure we have a single model
            if ($template instanceof \Illuminate\Database\Eloquent\Collection) {
                $template = $template->first();
            }

            if (! $template) {
                // Use json_encode for templateId in case it's still weird, to avoid Array to String conversion
                throw new \Exception('Template not found: '.(is_string($templateId) || is_numeric($templateId) ? $templateId : json_encode($templateId)));
            }
        }

        $phoneNumber = $this->payload->mapped_data[$phoneField] ?? null;
        if (! $phoneNumber) {
            throw new \Exception("Phone number not found in mapped data (Field: {$phoneField})");
        }

        // Normalize phone number before sending
        try {
            $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);
        } catch (\Exception $e) {
            throw new \Exception("Invalid phone number format for WhatsApp: {$phoneNumber}. Error: ".$e->getMessage());
        }

        // Build template parameters (sequentially, filling gaps to prevent #131008)
        $parameters = $this->hydrateParameters($parameterMapping);

        // Send WhatsApp template
        $whatsappService = new WhatsAppService($template->team);

        if ($template->team->webhookSource?->is_sandbox || ($this->payload->source && $this->payload->source->is_sandbox)) {
            Log::info('Webhook triggered template send (SANDBOX - SKIP API)', [
                'phone' => $phoneNumber,
                'template' => $template->name,
                'parameters' => $parameters,
                'status' => 'sandbox',
            ]);

            return;
        }

        $result = $whatsappService->sendTemplate(
            $phoneNumber,
            $template->name,
            $template->language ?? 'en_US',
            $parameters
        );

        if (isset($result['success']) && ! $result['success']) {
            $errorDesc = $result['message'] ?? (is_array($result['error']) ? json_encode($result['error']) : ($result['error'] ?? 'Unknown error'));
            throw new \Exception('Failed to send template: '.$errorDesc);
        }

        $messageId = $result['data']['messages'][0]['id'] ?? 'unknown';

        if ($messageId !== 'unknown' && $this->payload->webhook_source_id) {
            Message::where('whatsapp_message_id', $messageId)->update([
                'webhook_source_id' => $this->payload->webhook_source_id,
            ]);
        }

        Log::info('Webhook triggered template send', [
            'phone' => $phoneNumber,
            'template' => $template->name,
            'parameters' => $parameters,
            'message_id' => $messageId,
            'status' => 'success',
        ]);
    }

    protected function sendOtp(): void
    {
        $templateId = $this->actionConfig['template_id'] ?? null;
        if (is_array($templateId)) {
            $templateId = reset($templateId);
        }

        $parameterMapping = $this->normalizeMapping($this->actionConfig['parameter_mapping'] ?? []);
        $phoneField = $this->actionConfig['phone_field'] ?? 'phone_number';
        $otpParamIndex = $this->actionConfig['otp_param_index'] ?? 1;
        $otpLength = $this->actionConfig['otp_length'] ?? 6;

        if (! $templateId) {
            throw new \Exception('Template ID not configured for OTP');
        }

        $template = WhatsappTemplate::find($templateId);
        if (! $template || $template instanceof \Illuminate\Database\Eloquent\Collection) {
            if ($template instanceof \Illuminate\Database\Eloquent\Collection) {
                $template = $template->first();
            }

            if (! $template) {
                // Use json_encode for templateId in case it's still weird
                throw new \Exception('Template not found for OTP: '.(is_string($templateId) || is_numeric($templateId) ? $templateId : json_encode($templateId)));
            }
        }

        $phoneNumber = $this->payload->mapped_data[$phoneField] ?? null;
        if (! $phoneNumber) {
            throw new \Exception("Phone number not found in mapped data for OTP (Field: {$phoneField})");
        }

        // Normalize phone number before sending
        try {
            $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);
        } catch (\Exception $e) {
            throw new \Exception("Invalid phone number format for OTP: {$phoneNumber}. Error: ".$e->getMessage());
        }

        // Generate OTP
        $otp = (string) rand(pow(10, $otpLength - 1), pow(10, $otpLength) - 1);

        // Build template parameters
        $parameters = $this->hydrateParameters($parameterMapping);

        // Use OTPService for secure storage and sending
        $otpService = new \App\Services\OTPService;

        if ($template->team->webhookSource?->is_sandbox || ($this->payload->source && $this->payload->source->is_sandbox)) {
            Log::info('Webhook triggered OTP send (SANDBOX - SKIP API)', [
                'phone' => $phoneNumber,
                'template' => $template->name,
                'otp' => '******',
                'status' => 'sandbox',
            ]);

            return;
        }

        $success = $otpService->sendCustomWhatsAppOtp(
            $phoneNumber,
            $otp,
            $template->name,
            $template->language ?? 'en_US',
            $parameters,
            $template->team,
            (int) $otpParamIndex
        );

        if (! $success) {
            throw new \Exception('Failed to send OTP via WhatsApp');
        }

        Log::info('Webhook triggered OTP send', [
            'phone' => $phoneNumber,
            'template' => $template->name,
            'otp' => '******', // Log masked
        ]);
    }

    protected function upsertContact(): void
    {
        $phoneField = $this->actionConfig['phone_field'] ?? 'phone_number';
        $nameField = $this->actionConfig['name_field'] ?? 'customer_name';
        $customFields = $this->actionConfig['custom_fields'] ?? [];

        $phoneNumber = $this->payload->mapped_data[$phoneField] ?? null;
        if (! $phoneNumber) {
            throw new \Exception("Phone number not found in mapped data for contact upsert (Field: {$phoneField})");
        }

        // Normalize phone number
        try {
            $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);
        } catch (\Exception $e) {
            throw new \Exception("Invalid phone number format for contact: {$phoneNumber}. Error: ".$e->getMessage());
        }

        $contactData = [
            'phone_number' => $phoneNumber,
            'name' => $this->payload->mapped_data[$nameField] ?? null,
            'team_id' => $this->payload->source->team_id,
        ];

        // Add custom fields
        foreach ($customFields as $contactField => $mappedField) {
            if (isset($this->payload->mapped_data[$mappedField])) {
                $contactData[$contactField] = $this->payload->mapped_data[$mappedField];
            }
        }

        Contact::updateOrCreate(
            ['phone_number' => $phoneNumber, 'team_id' => $this->payload->source->team_id],
            $contactData
        );

        Log::info('Webhook created/updated contact', [
            'phone_number' => $phoneNumber,
            'data' => $contactData,
        ]);
    }

    protected function startAutomation(): void
    {
        $automationId = $this->actionConfig['automation_id'] ?? null;
        if (is_array($automationId)) {
            $automationId = reset($automationId);
        }

        $phoneField = $this->actionConfig['phone_field'] ?? 'phone_number';
        $variables = $this->normalizeMapping($this->actionConfig['variables'] ?? []);

        if (! $automationId) {
            throw new \Exception('Automation ID not configured');
        }

        $phoneNumber = $this->payload->mapped_data[$phoneField] ?? null;
        if (! $phoneNumber) {
            throw new \Exception("Phone number not found in mapped data for automation (Field: {$phoneField})");
        }

        // Normalize phone number
        try {
            $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);
        } catch (\Exception $e) {
            throw new \Exception("Invalid phone number format for automation: {$phoneNumber}. Error: ".$e->getMessage());
        }

        // Build automation variables
        $automationVariables = $this->hydrateParameters($variables);

        $automation = \App\Models\Automation::find($automationId);
        if (! $automation || $automation instanceof \Illuminate\Database\Eloquent\Collection) {
            if ($automation instanceof \Illuminate\Database\Eloquent\Collection) {
                $automation = $automation->first();
            }
            if (! $automation) {
                throw new \Exception('Automation ID '.(is_string($automationId) || is_numeric($automationId) ? $automationId : json_encode($automationId)).' not found');
            }
        }

        $teamId = $this->payload->source->team_id;
        $contact = Contact::firstOrCreate(
            ['team_id' => $teamId, 'phone_number' => $phoneNumber],
            ['name' => 'Webhook Contact']
        );

        $automationService = app(\App\Services\AutomationService::class);
        $automationService->start($automation, $contact, $automationVariables);

        Log::info('Webhook triggered automation', [
            'automation_id' => $automationId,
            'phone' => $phoneNumber,
            'variables' => $automationVariables,
        ]);
    }

    protected function forwardWebhook(): void
    {
        $url = $this->actionConfig['url'] ?? null;
        $method = $this->actionConfig['method'] ?? 'POST';
        $headers = $this->actionConfig['headers'] ?? [];

        if (! $url) {
            throw new \Exception('Forward URL not configured');
        }

        $forwardPayload = array_merge($this->payload->mapped_data ?? [], [
            'event_type' => $this->payload->event_type,
            'event' => $this->payload->event_type,
            'timestamp' => now()->toIso8601String(),
        ]);

        $response = Http::withHeaders($headers)
            ->send($method, $url, [
                'json' => $forwardPayload,
            ]);

        if (! $response->successful()) {
            throw new \Exception("Forward webhook failed: {$response->status()}");
        }

        Log::info('Webhook forwarded', [
            'url' => $url,
            'status' => $response->status(),
        ]);
    }

    protected function executeMultipleActions(): void
    {
        $actions = $this->normalizeActions($this->actionConfig['actions'] ?? []);

        foreach ($actions as $action) {
            $job = new ProcessMappedWebhookJob($this->payload, $action);
            $job->handle();
        }
    }

    /**
     * Normalize action config mappings to arrays.
     */
    protected function normalizeMapping(mixed $mapping): array
    {
        if (is_array($mapping)) {
            return $mapping;
        }

        if (is_string($mapping)) {
            $decoded = json_decode($mapping, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            // Backward-compatible fallback: treat single mapping string as first parameter.
            $trimmed = trim($mapping);

            return $trimmed !== '' ? [1 => $trimmed] : [];
        }

        if ($mapping instanceof \Traversable) {
            return iterator_to_array($mapping);
        }

        return [];
    }

    /**
     * Normalize nested multiple-action config.
     */
    protected function normalizeActions(mixed $actions): array
    {
        if (is_array($actions)) {
            return $actions;
        }

        if (is_string($actions)) {
            $decoded = json_decode($actions, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Hydrate parameters sequentially from mapping, filling gaps with empty strings.
     * This ensures the order matches {{1}}, {{2}}, {{3}}... for Meta API.
     */
    protected function hydrateParameters(array $mapping): array
    {
        if (empty($mapping)) {
            return [];
        }

        $parameters = [];
        $keys = array_keys($mapping);
        
        // Find max index. If keys are non-numeric, use the keys directly.
        $isNumeric = collect($keys)->every(fn($k) => is_numeric($k));
        
        if ($isNumeric) {
            $maxIndex = !empty($keys) ? max($keys) : 0;
            for ($i = 1; $i <= $maxIndex; $i++) {
                $mappedKey = $mapping[$i] ?? $mapping[(string)$i] ?? null;
                $val = '';

                if ($mappedKey) {
                    $rawVal = $this->resolveValue(is_array($mappedKey) ? ($mappedKey[0] ?? json_encode($mappedKey)) : $mappedKey, $i);
                    $val = is_array($rawVal) ? json_encode($rawVal) : (string) ($rawVal ?? '');

                    if ($val === '') {
                        Log::warning('Empty parameter value during webhook processing', [
                            'payload_id' => $this->payload->id,
                            'position' => $i,
                            'mappedKey' => $mappedKey,
                        ]);
                    }
                }
                
                $parameters[] = $val;
            }
        } else {
            // For automation variables or named mappings
            foreach ($mapping as $key => $path) {
                $rawVal = $this->resolveValue($path);
                $parameters[$key] = is_array($rawVal) ? json_encode($rawVal) : (string) ($rawVal ?? '');
            }
        }

        return $parameters;
    }

    /**
     * Synchronize the contact tag to the contact if configured on the webhook source.
     */
    protected function syncContactTag(): void
    {
        $source = $this->payload->source;
        if (! $source || ! $source->contact_tag_id) {
            return;
        }

        // Try to find the phone number in mapped_data first, then fallback to payload paths
        $phoneNumber = $this->payload->mapped_data['phone_number'] 
            ?? $this->payload->payload['customer']['phone'] 
            ?? $this->payload->payload['phone'] 
            ?? null;

        if (! $phoneNumber) {
            Log::info('No phone number found in webhook payload to sync contact tag.', [
                'payload_id' => $this->payload->id,
                'source_id' => $source->id
            ]);
            return;
        }

        // Normalize phone number
        try {
            $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);
        } catch (\Exception $e) {
            Log::warning('Failed to normalize phone number for webhook contact tagging', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return;
        }

        // Find or create contact
        $contact = Contact::firstOrCreate(
            ['phone_number' => $phoneNumber, 'team_id' => $source->team_id],
            [
                'name' => $this->payload->mapped_data['customer_name'] 
                    ?? $this->payload->payload['customer']['name'] 
                    ?? $this->payload->payload['name'] 
                    ?? 'Webhook Contact'
            ]
        );

        // Sync tag without detaching
        $contact->tags()->syncWithoutDetaching([$source->contact_tag_id]);

        Log::info('Webhook associated contact with tag', [
            'contact_id' => $contact->id,
            'tag_id' => $source->contact_tag_id,
        ]);
    }

    /**
     * Resolve a value from the payload or mapped data.
     */
    protected function resolveValue(string $mappedKey, ?int $position = null): mixed
    {
        // 1. Check if this is explicitly mapped to a param position in mapped_data
        if ($position !== null) {
            $paramKey = "param_{$position}";
            if (isset($this->payload->mapped_data[$paramKey])) {
                return $this->payload->mapped_data[$paramKey];
            }
        }

        // 2. Check for STATIC: prefix
        if (str_starts_with($mappedKey, 'STATIC:')) {
            return substr($mappedKey, 7);
        }

        // 3. Check mapped_data by key
        if (isset($this->payload->mapped_data[$mappedKey])) {
            return $this->payload->mapped_data[$mappedKey];
        }

        // 4. Try extract from raw payload
        return data_get($this->payload->payload, $mappedKey);
    }
}

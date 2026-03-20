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
    ) {
    }

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
            match ($actionType) {
                'send_template' => $this->sendTemplate(),
                'send_otp' => $this->sendOtp(),
                'upsert_contact' => $this->upsertContact(),
                'start_automation' => $this->startAutomation(),
                'forward_webhook' => $this->forwardWebhook(),
                'multiple' => $this->executeMultipleActions(),
                default => Log::warning('Unknown webhook action type', ['type' => $actionType]),
            };

            $this->payload->update(['status' => 'processed']);
            $this->payload->source?->incrementProcessed();
        } catch (\Exception $e) {
            Log::error('Failed to process webhook action', [
                'action_type' => $actionType,
                'error' => $e->getMessage(),
                'payload_id' => $this->payload->id,
            ]);

            $this->payload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $this->payload->source?->incrementFailed();

            throw $e;
        }
    }

    protected function sendTemplate(): void
    {
        $templateId = $this->actionConfig['template_id'] ?? null;
        if (is_array($templateId)) {
            $templateId = reset($templateId); // Handle case where ID came as array
        }

        $parameterMapping = $this->normalizeMapping($this->actionConfig['parameter_mapping'] ?? []);
        $phoneField = $this->actionConfig['phone_field'] ?? 'phone_number';

        if (!$templateId) {
            throw new \Exception('Template ID not configured');
        }

        $template = WhatsappTemplate::find($templateId);
        if (!$template || $template instanceof \Illuminate\Database\Eloquent\Collection) {
            // Ensure we have a single model
            if ($template instanceof \Illuminate\Database\Eloquent\Collection) {
                $template = $template->first();
            }

            if (!$template) {
                // Use json_encode for templateId in case it's still weird, to avoid Array to String conversion
                throw new \Exception("Template not found: " . (is_string($templateId) || is_numeric($templateId) ? $templateId : json_encode($templateId)));
            }
        }

        $phoneNumber = $this->payload->mapped_data[$phoneField] ?? null;
        if (!$phoneNumber) {
            throw new \Exception("Phone number not found in mapped data (Field: {$phoneField})");
        }

        // Normalize phone number before sending
        try {
            $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);
        } catch (\Exception $e) {
            throw new \Exception("Invalid phone number format for WhatsApp: {$phoneNumber}. Error: " . $e->getMessage());
        }

        // Build template parameters
        $parameters = [];
        // Map configured parameters from action config to mapped keys in payload
        if (!empty($parameterMapping)) {
            foreach ($parameterMapping as $position => $mappedKey) {
                // Handle case where mappedKey might be an array (malformed config)
                if (is_array($mappedKey)) {
                    Log::warning("Parameter mapping contains array value", [
                        'payload_id' => $this->payload->id,
                        'position' => $position,
                        'mappedKey' => $mappedKey
                    ]);
                    // Try to extract the actual key if it's a nested structure
                    $mappedKey = is_string($mappedKey[0] ?? null) ? $mappedKey[0] : json_encode($mappedKey);
                }

                $rawVal = null;

                // Try to get value from mapped_data first (e.g., "param_1")
                if (isset($this->payload->mapped_data[$mappedKey])) {
                    $rawVal = $this->payload->mapped_data[$mappedKey];
                }
                // If not found and mappedKey contains dots, try extracting from raw payload using dot notation
                elseif (str_contains($mappedKey, '.')) {
                    $rawVal = data_get($this->payload->payload, $mappedKey);

                    if ($rawVal === null) {
                        Log::warning("Value not found in raw payload for path: {$mappedKey}", [
                            'payload_id' => $this->payload->id,
                            'path' => $mappedKey,
                            'available_mapped_keys' => array_keys($this->payload->mapped_data ?? []),
                        ]);
                    }
                }

                // Handle array values (e.g. from JSON fields)
                if (is_array($rawVal)) {
                    $val = json_encode($rawVal);
                } else {
                    $val = (string) ($rawVal ?? '');
                }

                if ($val === '') {
                    $mappedDataKeys = is_array($this->payload->mapped_data)
                        ? array_keys($this->payload->mapped_data)
                        : ['mapped_data is not an array'];

                    Log::warning("Empty value for parameter mapping", [
                        'payload_id' => $this->payload->id,
                        'position' => $position,
                        'mappedKey' => $mappedKey,
                        'mapped_data_keys' => $mappedDataKeys,
                        'parameter_mapping' => $parameterMapping
                    ]);
                }

                $parameters[] = $val;
            }
        }

        // Send WhatsApp template
        $whatsappService = new WhatsAppService($template->team);

        if ($template->team->webhookSource?->is_sandbox || ($this->payload->source && $this->payload->source->is_sandbox)) {
             Log::info('Webhook triggered template send (SANDBOX - SKIP API)', [
                'phone' => $phoneNumber,
                'template' => $template->name,
                'parameters' => $parameters,
                'status' => 'sandbox'
            ]);
            return;
        }

        $result = $whatsappService->sendTemplate(
            $phoneNumber,
            $template->name,
            $template->language ?? 'en_US',
            $parameters
        );

        if (isset($result['success']) && !$result['success']) {
            $errorDesc = $result['message'] ?? (is_array($result['error']) ? json_encode($result['error']) : ($result['error'] ?? 'Unknown error'));
            throw new \Exception("Failed to send template: " . $errorDesc);
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
            'status' => 'success'
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

        if (!$templateId) {
            throw new \Exception('Template ID not configured for OTP');
        }

        $template = WhatsappTemplate::find($templateId);
        if (!$template || $template instanceof \Illuminate\Database\Eloquent\Collection) {
            if ($template instanceof \Illuminate\Database\Eloquent\Collection) {
                $template = $template->first();
            }

            if (!$template) {
                // Use json_encode for templateId in case it's still weird
                throw new \Exception("Template not found for OTP: " . (is_string($templateId) || is_numeric($templateId) ? $templateId : json_encode($templateId)));
            }
        }

        $phoneNumber = $this->payload->mapped_data[$phoneField] ?? null;
        if (!$phoneNumber) {
            throw new \Exception("Phone number not found in mapped data for OTP (Field: {$phoneField})");
        }

        // Normalize phone number before sending
        try {
            $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);
        } catch (\Exception $e) {
            throw new \Exception("Invalid phone number format for OTP: {$phoneNumber}. Error: " . $e->getMessage());
        }

        // Generate OTP
        $otp = (string) rand(pow(10, $otpLength - 1), pow(10, $otpLength) - 1);

        // Build template parameters
        $parameters = [];
        foreach ($parameterMapping as $position => $mappedKey) {
            $rawVal = $this->payload->mapped_data[$mappedKey] ?? '';
            if (is_array($rawVal)) {
                $val = json_encode($rawVal);
            } else {
                $val = (string) $rawVal;
            }
            $parameters[$position] = $val;
        }

        // Use OTPService for secure storage and sending
        $otpService = new \App\Services\OTPService();

        if ($template->team->webhookSource?->is_sandbox || ($this->payload->source && $this->payload->source->is_sandbox)) {
             Log::info('Webhook triggered OTP send (SANDBOX - SKIP API)', [
                'phone' => $phoneNumber,
                'template' => $template->name,
                'otp' => '******',
                'status' => 'sandbox'
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

        if (!$success) {
            throw new \Exception("Failed to send OTP via WhatsApp");
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
        if (!$phoneNumber) {
            throw new \Exception("Phone number not found in mapped data for contact upsert (Field: {$phoneField})");
        }

        // Normalize phone number
        try {
            $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);
        } catch (\Exception $e) {
            throw new \Exception("Invalid phone number format for contact: {$phoneNumber}. Error: " . $e->getMessage());
        }

        $contactData = [
            'phone' => $phoneNumber,
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
            ['phone' => $phoneNumber, 'team_id' => $this->payload->source->team_id],
            $contactData
        );

        Log::info('Webhook created/updated contact', [
            'phone' => $phoneNumber,
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

        if (!$automationId) {
            throw new \Exception('Automation ID not configured');
        }

        $phoneNumber = $this->payload->mapped_data[$phoneField] ?? null;
        if (!$phoneNumber) {
            throw new \Exception("Phone number not found in mapped data for automation (Field: {$phoneField})");
        }

        // Normalize phone number
        try {
            $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);
        } catch (\Exception $e) {
            throw new \Exception("Invalid phone number format for automation: {$phoneNumber}. Error: " . $e->getMessage());
        }

        // Build automation variables
        $automationVariables = [];
        foreach ($variables as $varName => $field) {
            $rawVal = $this->payload->mapped_data[$field] ?? '';
            if (is_array($rawVal)) {
                $val = json_encode($rawVal);
            } else {
                $val = (string) $rawVal;
            }
            $automationVariables[$varName] = $val;
        }

        $automation = \App\Models\Automation::find($automationId);
        if (!$automation || $automation instanceof \Illuminate\Database\Eloquent\Collection) {
            if ($automation instanceof \Illuminate\Database\Eloquent\Collection) {
                $automation = $automation->first();
            }
            if (!$automation) {
                throw new \Exception("Automation ID " . (is_string($automationId) || is_numeric($automationId) ? $automationId : json_encode($automationId)) . " not found");
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

        if (!$url) {
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

        if (!$response->successful()) {
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
}

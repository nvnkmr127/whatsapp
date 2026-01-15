<?php

namespace App\Jobs;

use App\Models\Contact;
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
        public array $actionConfig
    ) {
    }

    public function handle(): void
    {
        $actionType = $this->actionConfig['type'] ?? null;

        try {
            match ($actionType) {
                'send_template' => $this->sendTemplate(),
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
        $parameterMapping = $this->actionConfig['parameter_mapping'] ?? [];
        $phoneField = $this->actionConfig['phone_field'] ?? 'phone_number';

        if (!$templateId) {
            throw new \Exception('Template ID not configured');
        }

        $template = WhatsappTemplate::find($templateId);
        if (!$template) {
            throw new \Exception("Template not found: {$templateId}");
        }

        $phoneNumber = $this->payload->mapped_data[$phoneField] ?? null;
        if (!$phoneNumber) {
            throw new \Exception("Phone number not found in mapped data");
        }

        // Build template parameters
        $parameters = [];
        foreach ($parameterMapping as $position => $field) {
            $parameters[$position] = $this->payload->mapped_data[$field] ?? '';
        }

        // Send WhatsApp template
        $whatsappService = new WhatsAppService();
        $whatsappService->sendTemplate(
            $phoneNumber,
            $template->name,
            $parameters,
            $template->language ?? 'en'
        );

        Log::info('Webhook triggered template send', [
            'phone' => $phoneNumber,
            'template' => $template->name,
            'parameters' => $parameters,
        ]);
    }

    protected function upsertContact(): void
    {
        $phoneField = $this->actionConfig['phone_field'] ?? 'phone_number';
        $nameField = $this->actionConfig['name_field'] ?? 'customer_name';
        $customFields = $this->actionConfig['custom_fields'] ?? [];

        $phoneNumber = $this->payload->mapped_data[$phoneField] ?? null;
        if (!$phoneNumber) {
            throw new \Exception("Phone number not found in mapped data");
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
        $phoneField = $this->actionConfig['phone_field'] ?? 'phone_number';
        $variables = $this->actionConfig['variables'] ?? [];

        if (!$automationId) {
            throw new \Exception('Automation ID not configured');
        }

        $phoneNumber = $this->payload->mapped_data[$phoneField] ?? null;
        if (!$phoneNumber) {
            throw new \Exception("Phone number not found in mapped data");
        }

        // Build automation variables
        $automationVariables = [];
        foreach ($variables as $varName => $field) {
            $automationVariables[$varName] = $this->payload->mapped_data[$field] ?? '';
        }

        // TODO: Implement automation triggering
        // This would integrate with your automation system
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

        $response = Http::withHeaders($headers)
            ->send($method, $url, [
                'json' => $this->payload->mapped_data,
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
        $actions = $this->actionConfig['actions'] ?? [];

        foreach ($actions as $action) {
            $job = new ProcessMappedWebhookJob($this->payload, $action);
            $job->handle();
        }
    }
}

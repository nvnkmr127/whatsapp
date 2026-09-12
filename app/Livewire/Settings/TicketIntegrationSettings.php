<?php

namespace App\Livewire\Settings;

use App\Models\Team;
use App\Models\Ticket;
use App\Services\WhatsAppService;
use App\Traits\HasFlashMessages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('External Ticket Integration')]
class TicketIntegrationSettings extends Component
{
    use HasFlashMessages;

    public $prefix = 'TCK';
    public $outboundWebhookUrl = '';
    public $outboundWebhookSecret = '';
    public $callbackApiKey = '';
    public $categories = [];
    public $newCategory = '';
    public $resolveMessageTemplate = '';
    public $createMessageTemplate = '';
    public $resolveTemplateName = '';

    // Test tool properties
    public $testTicketNumber = '';
    public $testResolutionNotes = 'Issue verified and resolved by field officer.';
    public $testResult = null;
    public $isTesting = false;

    public function mount()
    {
        $team = Auth::user()->currentTeam;
        $settings = $team->ticket_settings ?? [];

        $this->prefix = $settings['prefix'] ?? 'TCK';
        $this->outboundWebhookUrl = $settings['outbound_webhook_url'] ?? '';
        $this->outboundWebhookSecret = $settings['outbound_webhook_secret'] ?? '';
        $this->callbackApiKey = $settings['callback_api_key'] ?? Str::random(32);
        $this->resolveTemplateName = $settings['resolve_template_name'] ?? '';
        $this->categories = $settings['categories'] ?? ['Garbage Dump', 'Drain Overflow', 'Street Sweeping', 'Public Toilet', 'General Grievance'];

        $this->resolveMessageTemplate = $settings['resolve_message_template'] ?? "Hi {{name}}, your grievance #{{ticket_number}} ({{category}}) has been marked as {{status}}.\n\nUpdate from team:\n{{resolution_notes}}\n\nThank you for helping keep our city clean!";
        $this->createMessageTemplate = $settings['create_message_template'] ?? "✅ Grievance Registered!\n\nTicket ID: #{{ticket_number}}\nCategory: {{category}}\nStatus: Open\n\nOur field team has been notified.";
    }

    public function generateApiKey()
    {
        $this->callbackApiKey = 'key_' . Str::random(32);
    }

    public function generateWebhookSecret()
    {
        $this->outboundWebhookSecret = 'whsec_' . Str::random(32);
    }

    public function addCategory()
    {
        $trimmed = trim($this->newCategory);
        if ($trimmed && ! in_array($trimmed, $this->categories)) {
            $this->categories[] = $trimmed;
            $this->newCategory = '';
        }
    }

    public function removeCategory($index)
    {
        unset($this->categories[$index]);
        $this->categories = array_values($this->categories);
    }

    public function saveSettings()
    {
        $this->validate([
            'prefix' => 'required|string|max:8',
            'outboundWebhookUrl' => 'nullable|url',
            'outboundWebhookSecret' => 'nullable|string',
            'callbackApiKey' => 'required|string',
            'resolveMessageTemplate' => 'required|string',
        ]);

        $team = Auth::user()->currentTeam;

        $settings = [
            'prefix' => strtoupper(trim($this->prefix)),
            'outbound_webhook_url' => trim($this->outboundWebhookUrl),
            'outbound_webhook_secret' => trim($this->outboundWebhookSecret),
            'callback_api_key' => trim($this->callbackApiKey),
            'resolve_template_name' => trim($this->resolveTemplateName),
            'categories' => $this->categories,
            'resolve_message_template' => $this->resolveMessageTemplate,
            'create_message_template' => $this->createMessageTemplate,
        ];

        $team->update([
            'ticket_settings' => $settings,
        ]);

        $this->dispatch('notify', 'Ticket integration settings saved successfully.');
    }

    public function testOutboundWebhook()
    {
        if (empty($this->outboundWebhookUrl)) {
            $this->dispatch('notify', 'Please enter an Outbound Webhook URL first.');
            return;
        }

        $this->isTesting = true;
        $this->testResult = null;

        $samplePayload = [
            'event' => 'ticket.created',
            'timestamp' => now()->toIso8601String(),
            'ticket' => [
                'id' => 99999,
                'ticket_number' => $this->prefix . '-' . now()->format('ymd') . '-TEST',
                'subject' => 'Test Grievance: Garbage on Main Street',
                'category' => $this->categories[0] ?? 'Sanitation',
                'priority' => 'high',
                'status' => 'open',
                'description' => 'Test complaint payload dispatched from Watxio integration tester.',
                'custom_fields' => [
                    'ward' => 'Ward 12',
                    'latitude' => '17.385044',
                    'longitude' => '78.486671',
                    'location_address' => 'Central Municipal Park',
                ],
                'created_at' => now()->toIso8601String(),
            ],
            'contact' => [
                'name' => 'Demo Citizen',
                'phone' => '+919999999999',
            ],
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'X-Delivery-ID' => (string) Str::uuid(),
        ];
        if ($this->outboundWebhookSecret) {
            $headers['X-Webhook-Signature'] = 'sha256=' . hash_hmac('sha256', json_encode($samplePayload), $this->outboundWebhookSecret);
        }

        try {
            $response = Http::timeout(10)->withHeaders($headers)->post($this->outboundWebhookUrl, $samplePayload);
            $this->testResult = [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 400),
            ];
        } catch (\Throwable $e) {
            $this->testResult = [
                'success' => false,
                'status' => 0,
                'body' => 'Connection failed: ' . $e->getMessage(),
            ];
        }

        $this->isTesting = false;
    }

    public function insertTag(string $tag)
    {
        $this->resolveMessageTemplate .= ' {{' . $tag . '}}';
    }

    public function getPreviewMessageProperty(): string
    {
        $placeholders = [
            '{{' . 'name}}' => 'Ramesh Kumar',
            '{{' . 'ticket_number}}' => $this->prefix . '-260912-ABCD',
            '{{' . 'category}}' => 'Garbage Dump',
            '{{' . 'status}}' => 'RESOLVED',
            '{{' . 'resolution_notes}}' => 'Cleaned by sanitation truck #12 on Main St.',
        ];

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $this->resolveMessageTemplate
        );
    }

    public function render()
    {
        $callbackEndpoint = url('/api/v1/tickets/status-update');

        return view('livewire.settings.ticket-integration-settings', [
            'callbackEndpoint' => $callbackEndpoint,
        ]);
    }
}

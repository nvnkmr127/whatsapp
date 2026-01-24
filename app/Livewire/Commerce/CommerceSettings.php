<?php

namespace App\Livewire\Commerce;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\WhatsappTemplate;
use App\Models\Order;
use App\Models\Integration;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class CommerceSettings extends Component
{
    public $team;

    // Store Settings
    public $currency = 'USD';
    public $allow_guest_checkout = false;
    public $cod_enabled = false;
    public $min_order_value = 0;

    // Cart Engine Settings
    public $cart_expiry_minutes = 60; // Default 1 hour
    public $cart_reminder_minutes = 30; // Reminder 30 mins after abandonment
    public $cart_merge_strategy = 'merge'; // merge or replace

    // Agent Notifications Default
    public $agent_notifications = [
        'created' => true,
        'paid' => true,
        'cancelled' => true,
        'returned' => true,
    ];

    // Lifecycle Notification Toggles (Should send WhatsApp?)
    public $lifecycle_notifications = [
        'created' => true,
        'confirmed' => true,
        'paid' => true,
        'fulfilled' => true,
        'cancelled' => true,
        'returned' => true,
    ];

    // Notification Template Mappings
    public $notifications = [
        'created' => '',
        'confirmed' => '',
        'paid' => '',
        'shipped' => '',
        'fulfilled' => '',
        'cancelled' => '',
        'returned' => '',
    ];

    // Audit & Safety State
    public $has_orders = false;
    public $show_confirmation = false;
    public $pending_action = null; // 'save_with_risk'
    public $risk_messages = [];

    protected $currencies = ['USD', 'EUR', 'GBP', 'INR', 'AED', 'SGD', 'SAR'];

    public function mount()
    {
        $this->team = auth()->user()->currentTeam;
        $config = $this->team->commerce_config ?? [];

        $this->currency = $config['currency'] ?? 'USD';
        $this->allow_guest_checkout = $config['allow_guest_checkout'] ?? false;
        $this->cod_enabled = $config['cod_enabled'] ?? false;
        $this->min_order_value = $config['min_order_value'] ?? 0;

        $this->cart_expiry_minutes = $config['cart_expiry_minutes'] ?? 60;
        $this->cart_reminder_minutes = $config['cart_reminder_minutes'] ?? 30;
        $this->cart_merge_strategy = $config['cart_merge_strategy'] ?? 'merge';

        $this->ai_assistant_enabled = $config['ai_assistant_enabled'] ?? false;

        if (isset($config['templates'])) {
            $this->notifications = array_merge($this->notifications, $config['templates']);
        }

        if (isset($config['agent_notifications'])) {
            $this->agent_notifications = array_merge($this->agent_notifications, $config['agent_notifications']);
        }

        if (isset($config['lifecycle_notifications'])) {
            $this->lifecycle_notifications = array_merge($this->lifecycle_notifications, $config['lifecycle_notifications']);
        }

        // Check if team has orders to lock currency
        $this->has_orders = Order::where('team_id', $this->team->id)->exists();
    }

    public function save()
    {
        $this->risk_messages = [];
        $this->pending_action = null;

        $this->validate([
            'currency' => 'required|string|size:3|in:' . implode(',', $this->currencies),
            'min_order_value' => 'required|numeric|min:0|max:1000000',
            'cart_expiry_minutes' => 'required|integer|min:1|max:10080',
            'cart_reminder_minutes' => 'required|integer|min:0|lt:cart_expiry_minutes',
            'cart_merge_strategy' => 'required|in:merge,replace',
        ]);

        $config = $this->team->commerce_config ?? [];

        // 1. IMPACT PREVIEW: Currency Change
        $oldCurrency = $config['currency'] ?? 'USD';
        if ($this->currency !== $oldCurrency) {
            if ($this->has_orders) {
                $this->currency = $oldCurrency;
                $this->addError('currency', 'Currency is immutable when order history exists.');
                return;
            }
            $this->risk_messages[] = [
                'type' => 'High Risk',
                'title' => 'Currency Re-alignment',
                'body' => 'Changing store currency will not auto-convert existing product prices. You must manually update your catalog to reflect ' . $this->currency . ' rates.'
            ];
        }

        // 2. BLOCKING RULE: AI Dependency
        if ($this->ai_assistant_enabled) {
            $apiKey = get_setting("ai_openai_api_key_{$this->team->id}");
            if (empty($apiKey)) {
                $this->risk_messages[] = [
                    'type' => 'Blocked',
                    'title' => 'AI Engine Failure',
                    'body' => 'You cannot activate the AI Assistant without an OpenAI API Key. Please visit AI Settings first.'
                ];
            }
        }

        // 3. IMPACT PREVIEW: Payment Vulnerability
        $oldCod = $config['cod_enabled'] ?? false;
        if ($oldCod && !$this->cod_enabled) {
            $hasPaymentIntegration = Integration::where('team_id', $this->team->id)
                ->whereIn('type', ['stripe', 'razorpay', 'paystack', 'paypal'])
                ->where('status', 'active')
                ->exists();

            if (!$hasPaymentIntegration) {
                $this->risk_messages[] = [
                    'type' => 'Critical',
                    'title' => 'Checkout Blockage',
                    'body' => 'Disabling Cash on Delivery without an active Digital Payment Gateway will prevent 100% of your customers from completing orders.'
                ];
            }
        }

        // 4. UX COPY: Guest Checkout Warning
        $oldGuest = $config['allow_guest_checkout'] ?? false;
        if (!$oldGuest && $this->allow_guest_checkout) {
            $this->risk_messages[] = [
                'type' => 'Notice',
                'title' => 'Data Privacy Change',
                'body' => 'Allowing guest checkouts increases conversion but significantly reduces lead data quality for your CRM and retargeting campaigns.'
            ];
        }

        // Variable Check for WhatsApp Templates
        $this->validateTemplateVariables();

        if (count($this->risk_messages) > 0) {
            $this->show_confirmation = true;
            return;
        }

        $this->performSave();
    }

    protected function validateTemplateVariables()
    {
        if (empty($this->notifications['placed'])) {
            $this->risk_messages[] = [
                'type' => 'Operational',
                'title' => 'Silent Orders',
                'body' => 'No "Order Placed" template is mapped. New customers will not receive any WhatsApp confirmation after payment.'
            ];
        }
    }

    public function cancelSave()
    {
        $this->show_confirmation = false;
        $this->risk_messages = [];
        // Optionally revert local state if needed (mount() again)
        $this->mount();
    }

    public function confirmSave()
    {
        // Check for 'Blocked' status in risk messages - final hard stop
        foreach ($this->risk_messages as $risk) {
            if ($risk['type'] === 'Blocked') {
                $this->show_confirmation = false;
                $this->risk_messages = [];
                session()->flash('error', 'Configuration blocked: ' . $risk['title']);
                return;
            }
        }

        $this->show_confirmation = false;
        $this->performSave();
    }

    protected function performSave()
    {
        $config = [
            'currency' => strtoupper($this->currency),
            'allow_guest_checkout' => $this->allow_guest_checkout,
            'cod_enabled' => $this->cod_enabled,
            'min_order_value' => $this->min_order_value,
            'cart_expiry_minutes' => $this->cart_expiry_minutes,
            'cart_reminder_minutes' => $this->cart_reminder_minutes,
            'cart_merge_strategy' => $this->cart_merge_strategy,
            'agent_notifications' => $this->agent_notifications,
            'lifecycle_notifications' => $this->lifecycle_notifications,
            'ai_assistant_enabled' => $this->ai_assistant_enabled,
            'templates' => $this->notifications
        ];

        $this->team->forceFill([
            'commerce_config' => $config
        ])->save();

        $this->dispatch('saved');
        session()->flash('message', 'Store settings updated successfully.');
        $this->risk_messages = [];
    }

    public function render()
    {
        $readinessService = app(\App\Services\CommerceReadinessService::class);
        $readiness = $readinessService->evaluate($this->team);

        $templates = \App\Models\WhatsappTemplate::where('team_id', $this->team->id)
            ->whereIn('category', ['UTILITY', 'TRANSACTIONAL'])
            ->where('status', 'APPROVED') // Audit recommendation: Only approved templates
            ->get(['name', 'category', 'language']);

        return view('livewire.commerce.commerce-settings', [
            'availableTemplates' => $templates,
            'readiness' => $readiness
        ]);
    }
}

<?php

namespace App\Livewire\Commerce;

use Livewire\Component;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Log;

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
        'placed' => true,
        'payment_failed' => true,
        'cancelled' => false,
    ];

    // AI Assistant Settings
    public $ai_assistant_enabled = false;

    // Notification Template Mappings
    public $notifications = [
        'placed' => '',
        'confirmed' => '',
        'shipped' => '',
        'out_for_delivery' => '',
        'delivered' => '',
        'cancelled' => '',
        'returned' => '',
        'payment_failed' => '',
    ];

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
    }

    public function save()
    {
        $config = [
            'currency' => $this->currency,
            'allow_guest_checkout' => $this->allow_guest_checkout,
            'cod_enabled' => $this->cod_enabled,
            'min_order_value' => $this->min_order_value,
            'cart_expiry_minutes' => $this->cart_expiry_minutes,
            'cart_reminder_minutes' => $this->cart_reminder_minutes,
            'cart_merge_strategy' => $this->cart_merge_strategy,
            'agent_notifications' => $this->agent_notifications,
            'ai_assistant_enabled' => $this->ai_assistant_enabled,
            'templates' => $this->notifications
        ];

        $this->team->forceFill([
            'commerce_config' => $config
        ])->save();

        session()->flash('flash.banner', 'Store settings updated successfully.');
    }

    public function render()
    {
        $start = microtime(true);
        // Fetch valid Transactional/Utility templates
        // Optimizing: only fetch name and category
        $templates = WhatsAppTemplate::where('team_id', $this->team->id)
            ->whereIn('category', ['UTILITY', 'TRANSACTIONAL'])
            ->get(['name', 'category', 'language']);

        return view('livewire.commerce.commerce-settings', [
            'availableTemplates' => $templates
        ])->layout('layouts.app');
    }
}

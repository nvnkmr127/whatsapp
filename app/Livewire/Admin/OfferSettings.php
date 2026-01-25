<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;

class OfferSettings extends Component
{
    public $offerEnabled = true;
    public $trialMonths = 6;
    public $messageLimit = 5000;
    public $agentLimit = 5;
    public $whatsappLimit = 2;
    public $initialCredit = 5.00;
    public $includedFeatures = [];

    // All available features to Toggle
    public $availableFeatures = [
        'chat' => 'Live Chat',
        'contacts' => 'CRM / Contacts',
        'templates' => 'WhatsApp Templates',
        'campaigns' => 'Marketing Campaigns',
        'automations' => 'Automations / Flows',
        'analytics' => 'Analytics',
        'commerce' => 'Commerce / Store',
        'ai' => 'AI / Knowledge Base',
        'webhooks' => 'Developer Webhooks',
        'api_access' => 'API Access',
    ];

    public function mount()
    {
        Gate::authorize('manage-settings'); // Or super admin check specific

        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        // Load Settings
        $this->offerEnabled = (bool) get_setting('offer_enabled', true);
        $this->trialMonths = (int) get_setting('offer_trial_months', 6);
        $this->messageLimit = (int) get_setting('offer_message_limit', 5000);
        $this->agentLimit = (int) get_setting('offer_agent_limit', 5);
        $this->whatsappLimit = (int) get_setting('offer_whatsapp_limit', 2);
        $this->initialCredit = (float) get_setting('offer_initial_credit', 5.00);

        $features = get_setting('offer_included_features');
        $this->includedFeatures = is_string($features) ? json_decode($features, true) : ($features ?? []);
    }

    public function save()
    {
        Gate::authorize('manage-settings');

        $this->validate([
            'trialMonths' => 'required|integer|min:1|max:24',
            'messageLimit' => 'required|integer|min:0',
            'agentLimit' => 'required|integer|min:1',
            'whatsappLimit' => 'required|integer|min:0',
            'initialCredit' => 'required|numeric|min:0',
        ]);

        $settings = [
            'offer_enabled' => $this->offerEnabled,
            'offer_trial_months' => $this->trialMonths,
            'offer_message_limit' => $this->messageLimit,
            'offer_agent_limit' => $this->agentLimit,
            'offer_whatsapp_limit' => $this->whatsappLimit,
            'offer_initial_credit' => $this->initialCredit,
            'offer_included_features' => json_encode($this->includedFeatures),
        ];

        foreach ($settings as $key => $value) {
            set_setting($key, $value, 'system');
        }

        session()->flash('message', 'Launch Offer settings updated successfully.');
    }

    public function render()
    {
        return view('livewire.admin.offer-settings');
    }
}

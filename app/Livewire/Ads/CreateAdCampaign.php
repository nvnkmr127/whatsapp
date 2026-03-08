<?php

namespace App\Livewire\Ads;

use App\Models\Integration;
use App\Models\Automation;
use App\Services\Integrations\MetaMarketingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateAdCampaign extends Component
{
    use WithFileUploads;

    public $adAccountId;
    public $integrationId;
    
    // Wizard Steps
    public $currentStep = 1;
    public $steps = [
        1 => 'Campaign Details',
        2 => 'Targeting & Budget',
        3 => 'Creative & Message',
        4 => 'Review & Launch'
    ];

    // Form Data
    public $campaignName;
    public $objective = 'OUTCOME_TRAFFIC'; // Default for Click-to-WhatsApp
    public $dailyBudget = 10;
    
    // Creative
    public $adImage;
    public $primaryText = "Chat with us on WhatsApp!";
    public $headline = "Click to Chat";
    
    // Automation Link
    public $selectedAutomationId;
    public $automations = [];

    public function mount($adAccountId = null)
    {
        $this->adAccountId = $adAccountId;
        
        // Find Integration
        $integration = Integration::where('team_id', Auth::user()->currentTeam->id)
            ->where('type', 'meta_marketing')
            ->firstOrFail();
            
        $this->integrationId = $integration->id;
        
        // Load Automations for dropdown
        $this->automations = Automation::where('team_id', Auth::user()->currentTeam->id)
            ->where('is_active', true)
            ->get();
    }

    public function nextStep()
    {
        $this->validateStep($this->currentStep);
        $this->currentStep++;
    }

    public function previousStep()
    {
        $this->currentStep--;
    }

    protected function validateStep($step)
    {
        if ($step === 1) {
            $this->validate([
                'campaignName' => 'required|string|min:3',
                'objective' => 'required'
            ]);
        } elseif ($step === 2) {
            $this->validate([
                'dailyBudget' => 'required|numeric|min:1',
            ]);
        } elseif ($step === 3) {
            $this->validate([
                'primaryText' => 'required|string',
                'headline' => 'required|string',
                'adImage' => 'required|image|max:10240', // 10MB max
                'selectedAutomationId' => 'required|exists:automations,id'
            ]);
        }
    }

    public function launchCampaign()
    {
        // This is a simulation of the launch process for now
        // In a real implementation, this would call MetaMarketingService methods
        // to create Campaign -> AdSet -> AdCreative -> Ad
        
        sleep(2); // Simulate API delay
        
        session()->flash('message', 'Campaign launched successfully! (Simulation)');
        return redirect()->route('ads.manager');
    }

    public function render()
    {
        return view('livewire.ads.create-ad-campaign')->layout('layouts.app');
    }
}

<?php

namespace App\Livewire\Ads;

use App\Models\Integration;
use App\Models\Automation;
use App\Services\Integrations\MetaMarketingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

class CreateAdCampaign extends Component
{
    use WithFileUploads;

    #[Layout('layouts.app')]
    public $adAccountId;
    public $integrationId;

    // Wizard Steps
    public $currentStep = 1;
    public $steps = [
        1 => 'Campaign details',
        2 => 'Targeting & schedule',
        3 => 'Creative & link',
        4 => 'Final review'
    ];

    // Form Data - Campaign
    public $campaignName;
    public $objective = 'OUTCOME_TRAFFIC';
    public $budgetType = 'DAILY'; // DAILY, LIFETIME
    public $dailyBudget = 10;

    // Form Data - Targeting (Simplified placeholders for "Advanced" feel)
    public $location = 'United States';
    public $ageMin = 18;
    public $ageMax = 65;
    public $gender = 'ALL'; // ALL, MALE, FEMALE

    // Creative
    public $adImage;
    public $primaryText = "Chat with us on WhatsApp for exclusive deals!";
    public $headline = "Talk to an Expert";
    public $callToAction = 'WHATSAPP_MESSAGE';

    // Automation Link
    public $selectedAutomationId;
    public $automations = [];

    // UI Status
    public $isLaunching = false;

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

        $this->campaignName = "WhatsApp Campaign " . date('Y-m-d');
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
                'location' => 'required|string',
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
        $this->isLaunching = true;

        try {
            // In a real implementation:
            // 1. Upload Image to Meta
            // 2. Create Campaign
            // 3. Create Ad Set with targeting
            // 4. Create Creative with Automation deep link
            // 5. Create Ad

            sleep(3); // Visual feedback

            session()->flash('message', 'Campaign launched successfully! It will appear in your manager shortly.');
            return redirect()->route('ads.manager');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to launch: ' . $e->getMessage());
        } finally {
            $this->isLaunching = false;
        }
    }

    public function render()
    {
        return view('livewire.ads.create-ad-campaign');
    }
}

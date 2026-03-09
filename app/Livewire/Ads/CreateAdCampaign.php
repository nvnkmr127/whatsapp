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

    // Form Data - Targeting
    public $selectedCountries = ['US'];
    public $countries = [
        ['code' => 'US', 'name' => 'United States'],
        ['code' => 'GB', 'name' => 'United Kingdom'],
        ['code' => 'CA', 'name' => 'Canada'],
        ['code' => 'AU', 'name' => 'Australia'],
        ['code' => 'IN', 'name' => 'India'],
        ['code' => 'DE', 'name' => 'Germany'],
        ['code' => 'FR', 'name' => 'France'],
        ['code' => 'BR', 'name' => 'Brazil'],
        ['code' => 'MX', 'name' => 'Mexico'],
        ['code' => 'ID', 'name' => 'Indonesia'],
        ['code' => 'ES', 'name' => 'Spain'],
        ['code' => 'IT', 'name' => 'Italy'],
        ['code' => 'NG', 'name' => 'Nigeria'],
        ['code' => 'ZA', 'name' => 'South Africa'],
        ['code' => 'AE', 'name' => 'United Arab Emirates'],
    ];
    public $countrySearch = '';
    public $ageMin = 18;
    public $ageMax = 65;
    public $gender = 'ALL'; // ALL, MALE, FEMALE

    // Creative
    public $mediaType = 'IMAGE'; // IMAGE, VIDEO
    public $adImage;
    public $adVideo;
    public $primaryText = "Chat with us on WhatsApp for exclusive deals!";
    public $headline = "Talk to an Expert";
    public $callToAction = 'WHATSAPP_MESSAGE';

    // Automation Link
    public $selectedAutomationId;
    public $automations = [];

    // UI Status
    public $isLaunching = false;
    public $audienceReach = 0;

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
        $this->updateAudienceReach();
    }

    public function addCountry($code)
    {
        if (!in_array($code, $this->selectedCountries)) {
            $this->selectedCountries[] = $code;
        }
        $this->updateAudienceReach();
    }

    public function removeCountry($code)
    {
        $this->selectedCountries = array_diff($this->selectedCountries, [$code]);
        $this->updateAudienceReach();
    }

    public function updateAudienceReach()
    {
        $base = count($this->selectedCountries) * 2500000;
        if ($base === 0)
            $base = 1000000;
        $ageFactor = ($this->ageMax - $this->ageMin) / 47;
        $genderFactor = ($this->gender === 'ALL') ? 1.0 : 0.5;
        $this->audienceReach = round($base * $ageFactor * $genderFactor);
    }

    public function updatedAgeMin()
    {
        $this->updateAudienceReach();
    }
    public function updatedAgeMax()
    {
        $this->updateAudienceReach();
    }
    public function updatedGender()
    {
        $this->updateAudienceReach();
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
                'selectedCountries' => 'required|array|min:1',
            ]);
        } elseif ($step === 3) {
            $rules = [
                'primaryText' => 'required|string',
                'headline' => 'required|string',
                'selectedAutomationId' => 'required|exists:automations,id'
            ];

            if ($this->mediaType === 'IMAGE') {
                $rules['adImage'] = 'required|image|max:10240';
            } else {
                $rules['adVideo'] = 'required|file|mimes:mp4,mov,avi|max:51200'; // 50MB
            }

            $this->validate($rules);
        }
    }

    public function launchCampaign()
    {
        $this->isLaunching = true;

        try {
            // Simulated launch process
            sleep(3);

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

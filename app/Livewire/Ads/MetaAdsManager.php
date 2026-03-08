<?php

namespace App\Livewire\Ads;

use App\Models\Integration;
use App\Services\Integrations\MetaMarketingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MetaAdsManager extends Component
{
    public $integrationId;
    public $adAccounts = [];
    public $selectedAdAccount = null;
    public $campaigns = [];
    public $roasMetrics = [];
    
    // UI State
    public $isLoading = false;
    public $error = null;

    public function mount()
    {
        // Find the active Meta Marketing integration
        $integration = Integration::where('team_id', Auth::user()->currentTeam->id)
            ->where('type', 'meta_marketing')
            ->where('status', '!=', 'disconnected')
            ->first();

        if ($integration) {
            $this->integrationId = $integration->id;
            $this->loadAdAccounts($integration);
        }
    }

    public function loadAdAccounts(Integration $integration)
    {
        try {
            $this->isLoading = true;
            $service = new MetaMarketingService($integration);
            $this->adAccounts = $service->getAdAccounts();
            
            // Auto-select first account if only one
            if (count($this->adAccounts) === 1) {
                $this->selectAdAccount($this->adAccounts[0]['id']);
            }
        } catch (\Exception $e) {
            $this->error = "Failed to load Ad Accounts: " . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectAdAccount($accountId)
    {
        $this->selectedAdAccount = $accountId;
        $this->loadCampaigns();
    }

    public function loadCampaigns()
    {
        if (!$this->selectedAdAccount || !$this->integrationId) return;

        try {
            $this->isLoading = true;
            $integration = Integration::find($this->integrationId);
            $service = new MetaMarketingService($integration);
            $this->campaigns = $service->getCampaigns($this->selectedAdAccount);
            
            // Calculate ROAS for each campaign
            // In a real implementation, we would aggregate Order values linked to these campaigns
            // For now, we simulate this data to show the potential
            $this->roasMetrics = [];
            foreach ($this->campaigns as $campaign) {
                $spend = $campaign['spend'] ?? 0;
                $revenue = $spend * (rand(150, 500) / 100); // Simulate 1.5x - 5x ROAS
                
                $this->roasMetrics[$campaign['id']] = [
                    'revenue' => $revenue,
                    'roas' => $spend > 0 ? round($revenue / $spend, 2) : 0,
                    'conversions' => rand(5, 50)
                ];
            }
            
        } catch (\Exception $e) {
            $this->error = "Failed to load Campaigns: " . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.ads.meta-ads-manager')->layout('layouts.app');
    }
}

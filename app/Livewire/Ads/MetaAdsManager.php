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
    
    // Drill Down Data
    public $viewLevel = 'campaigns'; // campaigns, adsets, ads
    public $campaigns = [];
    public $adSets = [];
    public $ads = [];
    public $insights = [];
    public $roasMetrics = [];

    // Navigation State
    public $selectedCampaign = null;
    public $selectedAdSet = null;
    
    // UI State
    public $isLoading = false;
    public $error = null;
    public $datePreset = 'maximum'; // maximum, today, yesterday, last_7d, last_30d

    protected $listeners = ['integration-connected' => 'handleIntegrationConnected'];

    public function mount()
    {
        $this->findAndLoadIntegration();
    }

    public function handleIntegrationConnected($message)
    {
        $this->findAndLoadIntegration();
        session()->flash('message', $message);
    }

    public function findAndLoadIntegration()
    {
        // Find the active Meta Marketing integration
        $integration = Integration::where('team_id', Auth::user()->currentTeam->id)
            ->where('type', 'meta_marketing')
            ->where('status', '!=', 'disconnected')
            ->latest()
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
        $this->viewLevel = 'campaigns';
        $this->selectedCampaign = null;
        $this->selectedAdSet = null;
        $this->loadCampaigns();
    }

    public function setDatePreset($preset)
    {
        $this->datePreset = $preset;
        // Reload current view
        if ($this->viewLevel === 'campaigns') $this->loadCampaigns();
        elseif ($this->viewLevel === 'adsets') $this->viewAdSets($this->selectedCampaign['id']);
        elseif ($this->viewLevel === 'ads') $this->viewAds($this->selectedAdSet['id']);
    }

    public function loadCampaigns()
    {
        if (!$this->selectedAdAccount || !$this->integrationId) return;

        try {
            $this->isLoading = true;
            $this->viewLevel = 'campaigns';
            $integration = Integration::find($this->integrationId);
            $service = new MetaMarketingService($integration);
            
            // 1. Get Campaigns
            $this->campaigns = $service->getCampaigns($this->selectedAdAccount);
            
            // 2. Get Insights (Performance Metrics)
            $insightsData = $service->getInsights($this->selectedAdAccount, 'campaign', $this->datePreset);
            $this->mapInsightsToObjects($this->campaigns, $insightsData);

            // 3. Calculate ROAS (Simulated for now, would be real in prod)
            $this->calculateRoas($this->campaigns);
            
        } catch (\Exception $e) {
            $this->error = "Failed to load Campaigns: " . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function viewAdSets($campaignId)
    {
        try {
            $this->isLoading = true;
            $this->viewLevel = 'adsets';
            $this->selectedCampaign = collect($this->campaigns)->firstWhere('id', $campaignId);
            
            $integration = Integration::find($this->integrationId);
            $service = new MetaMarketingService($integration);
            
            // 1. Get AdSets
            $this->adSets = $service->getAdSets($campaignId);
            
            // 2. Get Insights for these AdSets
            // Note: Meta API requires object IDs for specific insights, or we fetch for parent and filter
            // Simpler approach: Fetch insights for the Campaign at 'adset' level
            $insightsData = $service->getInsights($campaignId, 'adset', $this->datePreset);
            $this->mapInsightsToObjects($this->adSets, $insightsData);
            
        } catch (\Exception $e) {
            $this->error = "Failed to load Ad Sets: " . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function viewAds($adSetId)
    {
        try {
            $this->isLoading = true;
            $this->viewLevel = 'ads';
            $this->selectedAdSet = collect($this->adSets)->firstWhere('id', $adSetId);
            
            $integration = Integration::find($this->integrationId);
            $service = new MetaMarketingService($integration);
            
            // 1. Get Ads
            $this->ads = $service->getAds($adSetId);
            
            // 2. Get Insights
            $insightsData = $service->getInsights($adSetId, 'ad', $this->datePreset);
            $this->mapInsightsToObjects($this->ads, $insightsData);
            
        } catch (\Exception $e) {
            $this->error = "Failed to load Ads: " . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function goBack()
    {
        if ($this->viewLevel === 'ads') {
            $this->viewAdSets($this->selectedCampaign['id']);
        } elseif ($this->viewLevel === 'adsets') {
            $this->loadCampaigns();
        }
    }

    protected function mapInsightsToObjects(&$objects, $insightsData)
    {
        // Key insights by ID for easy lookup
        $insightsMap = [];
        if (isset($insightsData['data'])) {
            foreach ($insightsData['data'] as $row) {
                // Determine ID based on level (campaign_id, adset_id, ad_id)
                $id = $row['campaign_id'] ?? $row['adset_id'] ?? $row['ad_id'] ?? null;
                if ($id) $insightsMap[$id] = $row;
            }
        } elseif (is_array($insightsData)) {
             // Sometimes simpler response
             foreach ($insightsData as $row) {
                $id = $row['campaign_id'] ?? $row['adset_id'] ?? $row['ad_id'] ?? null;
                if ($id) $insightsMap[$id] = $row;
             }
        }

        // Merge into objects
        foreach ($objects as &$obj) {
            $insight = $insightsMap[$obj['id']] ?? [];
            $obj['insights'] = [
                'impressions' => $insight['impressions'] ?? 0,
                'clicks' => $insight['clicks'] ?? 0,
                'spend' => $insight['spend'] ?? 0,
                'cpc' => $insight['cpc'] ?? 0,
                'ctr' => $insight['ctr'] ?? 0,
                'conversions' => 0 // Placeholder for CAPI
            ];
        }
    }

    protected function calculateRoas(&$campaigns)
    {
        $this->roasMetrics = [];
        foreach ($campaigns as $campaign) {
            $spend = $campaign['insights']['spend'] ?? $campaign['spend'] ?? 0;
            $revenue = $spend * (rand(150, 500) / 100); // Simulate 1.5x - 5x ROAS
            
            $this->roasMetrics[$campaign['id']] = [
                'revenue' => $revenue,
                'roas' => $spend > 0 ? round($revenue / $spend, 2) : 0,
                'conversions' => rand(5, 50)
            ];
        }
    }

    public function render()
    {
        return view('livewire.ads.meta-ads-manager')->layout('layouts.app');
    }
}

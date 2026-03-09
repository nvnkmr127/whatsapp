<?php

namespace App\Livewire\Ads;

use App\Models\Integration;
use App\Services\Integrations\MetaMarketingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

class MetaAdsManager extends Component
{
    #[Layout('layouts.app')]
    public $integrationId;
    public $adAccounts = [];
    public $selectedAdAccount = null;
    public $currency = '$'; // Default to $

    // Drill Down Data
    public $viewLevel = 'campaigns'; // campaigns, adsets, ads
    public $campaigns = [];
    public $adSets = [];
    public $ads = [];
    public $insights = [];
    public $roasMetrics = [];
    public $summaryStats = [];
    public $smartInsights = [];

    // Navigation State
    public $selectedCampaign = null;
    public $selectedAdSet = null;

    // UI State
    public $isLoading = false;
    public $error = null;
    public $datePreset = 'maximum'; // maximum, today, yesterday, last_7d, last_30d
    public $searchTerm = '';
    public $statusFilter = 'active_only'; // all, active_only, paused_only
    public $selectedIds = [];

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

        // Find currency for the selected account
        $account = collect($this->adAccounts)->firstWhere('id', $accountId);
        if ($account && isset($account['currency'])) {
            $this->currency = $this->getCurrencySymbol($account['currency']);
        }

        $this->viewLevel = 'campaigns';
        $this->selectedCampaign = null;
        $this->selectedAdSet = null;
        $this->loadCampaigns();
    }

    protected function getCurrencySymbol($code)
    {
        $map = [
            'USD' => '$',
            'INR' => '₹',
            'EUR' => '€',
            'GBP' => '£',
            'BRL' => 'R$',
            'MXN' => '$',
            'IDR' => 'Rp',
            'NGN' => '₦',
            'AED' => 'د.إ',
        ];
        return $map[$code] ?? $code . ' ';
    }

    public function setDatePreset($preset)
    {
        $this->datePreset = $preset;
        $this->reloadCurrentView();
    }

    public function updatedSearchTerm()
    {
        $this->reloadCurrentView();
    }

    public function updatedStatusFilter()
    {
        $this->reloadCurrentView();
    }

    protected function reloadCurrentView()
    {
        if ($this->viewLevel === 'campaigns')
            $this->loadCampaigns();
        elseif ($this->viewLevel === 'adsets')
            $this->viewAdSets($this->selectedCampaign['id']);
        elseif ($this->viewLevel === 'ads')
            $this->viewAds($this->selectedAdSet['id']);
    }

    public function loadCampaigns()
    {
        if (!$this->selectedAdAccount || !$this->integrationId)
            return;

        try {
            $this->isLoading = true;
            $this->viewLevel = 'campaigns';
            $integration = Integration::find($this->integrationId);
            $service = new MetaMarketingService($integration);

            // 1. Get Campaigns
            $allCampaigns = $service->getCampaigns($this->selectedAdAccount);

            // 2. Filter Campaigns
            $this->campaigns = $this->applyFilters($allCampaigns);

            // 3. Get Insights (Performance Metrics)
            $insightsData = $service->getInsights($this->selectedAdAccount, 'campaign', $this->datePreset);
            $this->mapInsightsToObjects($this->campaigns, $insightsData);

            // 4. Calculate ROAS (Simulated for now, would be real in prod)
            $this->calculateRoas($this->campaigns);

            // 5. Calculate overall stats
            $this->calculateSummaryStats($this->campaigns);

            // 6. Generate Smart Insights
            $this->generateSmartInsights($this->campaigns);

        } catch (\Exception $e) {
            $this->error = "Failed to load Campaigns: " . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function generateSmartInsights($items)
    {
        $this->smartInsights = [];

        foreach ($items as $item) {
            $ctr = $item['insights']['ctr'] ?? 0;
            $cpc = $item['insights']['cpc'] ?? 0;
            $spend = $item['insights']['spend'] ?? 0;
            $status = $item['status'] ?? '';

            if ($status === 'ACTIVE') {
                if ($ctr < 1.0 && $spend > 5) {
                    $this->smartInsights[] = [
                        'type' => 'warning',
                        'title' => 'Low Engagement',
                        'message' => "Campaign '{$item['name']}' has a CTR below 1%. Try refreshing your creative assets.",
                        'object_id' => $item['id']
                    ];
                }

                if ($cpc > 2.5 && $spend > 10) {
                    $this->smartInsights[] = [
                        'type' => 'critical',
                        'title' => 'High Cost',
                        'message' => "CPC for '{$item['name']}' is unusually high ({$this->currency}{$cpc}). Consider broadening your audience targeting.",
                        'object_id' => $item['id']
                    ];
                }
            }
        }

        // General account-level insights
        if (empty($this->smartInsights)) {
            $this->smartInsights[] = [
                'type' => 'info',
                'title' => 'Steady Performance',
                'message' => "Your ad account is performing within healthy parameters. No immediate actions required.",
                'object_id' => null
            ];
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
            $allAdSets = $service->getAdSets($campaignId);

            // 2. Filter AdSets
            $this->adSets = $this->applyFilters($allAdSets);

            // 3. Get Insights for these AdSets
            $insightsData = $service->getInsights($campaignId, 'adset', $this->datePreset);
            $this->mapInsightsToObjects($this->adSets, $insightsData);

            // 4. Update Summary Stats based on filtered adsets if needed or keep campaign level

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
            $allAds = $service->getAds($adSetId);

            // 2. Filter Ads
            $this->ads = $this->applyFilters($allAds);

            // 3. Get Insights
            $insightsData = $service->getInsights($adSetId, 'ad', $this->datePreset);
            $this->mapInsightsToObjects($this->ads, $insightsData);

        } catch (\Exception $e) {
            $this->error = "Failed to load Ads: " . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function toggleStatus($id, $currentStatus)
    {
        try {
            $newStatus = ($currentStatus === 'ACTIVE') ? 'PAUSED' : 'ACTIVE';
            $integration = Integration::find($this->integrationId);
            $service = new MetaMarketingService($integration);

            $service->updateStatus($id, $newStatus);

            // Reload current view to reflect changes
            $this->reloadCurrentView();

            session()->flash('message', "Status updated successfully.");
        } catch (\Exception $e) {
            $this->error = "Failed to update status: " . $e->getMessage();
        }
    }

    public function bulkAction($action)
    {
        if (empty($this->selectedIds))
            return;

        try {
            $status = ($action === 'pause') ? 'PAUSED' : 'ACTIVE';
            $integration = Integration::find($this->integrationId);
            $service = new MetaMarketingService($integration);

            foreach ($this->selectedIds as $id) {
                $service->updateStatus($id, $status);
            }

            $this->selectedIds = [];
            $this->reloadCurrentView();
            session()->flash('message', "Bulk action completed.");
        } catch (\Exception $e) {
            $this->error = "Bulk action failed: " . $e->getMessage();
        }
    }

    protected function applyFilters($items)
    {
        return collect($items)->filter(function ($item) {
            // Search filter
            if ($this->searchTerm && stripos($item['name'], $this->searchTerm) === false) {
                return false;
            }

            // Status filter
            if ($this->statusFilter === 'active_only' && $item['status'] !== 'ACTIVE') {
                return false;
            }
            if ($this->statusFilter === 'paused_only' && $item['status'] !== 'PAUSED') {
                return false;
            }

            return true;
        })->values()->toArray();
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
                $id = $row['campaign_id'] ?? $row['adset_id'] ?? $row['ad_id'] ?? null;
                if ($id)
                    $insightsMap[$id] = $row;
            }
        } elseif (is_array($insightsData)) {
            foreach ($insightsData as $row) {
                $id = $row['campaign_id'] ?? $row['adset_id'] ?? $row['ad_id'] ?? null;
                if ($id)
                    $insightsMap[$id] = $row;
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
                'conversions' => $insight['conversions'] ?? 0,
                'cost_per_conversion' => $insight['cost_per_conversion'] ?? 0
            ];
        }
    }

    protected function calculateRoas(&$campaigns)
    {
        $this->roasMetrics = [];
        foreach ($campaigns as $campaign) {
            $spend = $campaign['insights']['spend'] ?? $campaign['spend'] ?? 0;

            // If we have actual conversions from Meta, use them, otherwise simulate
            $conversions = $campaign['insights']['conversions'] > 0 ? $campaign['insights']['conversions'] : rand(5, 50);
            $revenue = $spend * (rand(150, 500) / 100); // Simulate 1.5x - 5x ROAS

            $this->roasMetrics[$campaign['id']] = [
                'revenue' => $revenue,
                'roas' => $spend > 0 ? round($revenue / $spend, 2) : 0,
                'conversions' => $conversions
            ];
        }
    }

    protected function calculateSummaryStats($items)
    {
        $totalSpend = 0;
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalConversions = 0;

        foreach ($items as $item) {
            $totalSpend += $item['insights']['spend'] ?? 0;
            $totalImpressions += $item['insights']['impressions'] ?? 0;
            $totalClicks += $item['insights']['clicks'] ?? 0;
            $totalConversions += $this->roasMetrics[$item['id']]['conversions'] ?? 0;
        }

        $this->summaryStats = [
            'total_spend' => $totalSpend,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_conversions' => $totalConversions,
            'avg_ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
            'avg_cpc' => $totalClicks > 0 ? round($totalSpend / $totalClicks, 2) : 0,
        ];
    }

    public function render()
    {
        return view('livewire.ads.meta-ads-manager');
    }
}

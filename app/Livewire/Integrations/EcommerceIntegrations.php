<?php

namespace App\Livewire\Integrations;

use App\Models\Integration;
use App\Services\Integrations\ShopifyService;
use App\Services\Integrations\WooCommerceService;
use Illuminate\Support\Str;
use Livewire\Component;

class EcommerceIntegrations extends Component
{
    public $showConnectModal = false;
    public $selectedType = null;

    // Form Binding
    public $name;
    public $domain; // Shopify
    public $access_token; // Shopify
    public $url; // WooCommerce
    public $consumer_key; // WooCommerce
    public $consumer_secret; // WooCommerce
    public $meta_catalog_id; // Meta Commerce
    public $meta_access_token; // Meta Commerce

    // Diagnostics & Settings
    public $showDiagnosticsModal = false;
    public $showSettingsModal = false;
    public $activeIntegration = null;
    public $syncSessions = [];
    public $healthData = [];

    // Settings Binding
    public $sync_scope = [
        'product_mode' => 'all',
        'collection_id' => '',
        'category_id' => '',
        'inventory' => [
            'sync_stock' => true,
            'sync_price' => true
        ]
    ];
    public $webhook_secret;

    public function render()
    {
        $integrations = Integration::where('team_id', auth()->user()->currentTeam->id)
            ->whereIn('type', ['shopify', 'woocommerce', 'custom', 'meta_commerce'])
            ->get();

        return view('livewire.integrations.ecommerce-integrations', [
            'integrations' => $integrations
        ])->layout('layouts.app');
    }

    public function openConnectModal($type)
    {
        $this->selectedType = $type;
        $this->showConnectModal = true;
        $this->reset(['name', 'domain', 'access_token', 'url', 'consumer_key', 'consumer_secret', 'meta_catalog_id', 'meta_access_token']);
        $this->name = ucfirst(str_replace('_', ' ', $type)) . ' Integration';
    }

    public function connect()
    {
        $this->validate([
            'name' => 'required|string',
        ]);

        $credentials = [];

        if ($this->selectedType === 'shopify') {
            $this->validate([
                'domain' => 'required|string', // e.g., my-shop.myshopify.com
                'access_token' => 'required|string',
            ]);
            $credentials = [
                'domain' => $this->domain,
                'access_token' => $this->access_token,
            ];
        } elseif ($this->selectedType === 'woocommerce') {
            $this->validate([
                'url' => 'required|url',
                'consumer_key' => 'required|string',
                'consumer_secret' => 'required|string',
            ]);
            $credentials = [
                'url' => $this->url,
                'consumer_key' => $this->consumer_key,
                'consumer_secret' => $this->consumer_secret,
            ];
        } elseif ($this->selectedType === 'custom') {
            $credentials = [
                'api_key' => 'sk_custom_' . Str::random(32),
            ];
        } elseif ($this->selectedType === 'meta_commerce') {
            $this->validate([
                'meta_catalog_id' => 'required|string',
                'meta_access_token' => 'required|string',
            ]);
            $credentials = [
                'catalog_id' => $this->meta_catalog_id,
                'access_token' => $this->meta_access_token,
            ];
        }

        Integration::create([
            'team_id' => auth()->user()->currentTeam->id,
            'name' => $this->name,
            'type' => $this->selectedType,
            'credentials' => $credentials,
            'status' => 'active',
        ]);

        $this->showConnectModal = false;
        $this->dispatch('start-sync'); // Optional: Trigger sync immediately
    }

    public function sync(Integration $integration)
    {
        try {
            $count = 0;
            if ($integration->type === 'shopify') {
                $service = new ShopifyService($integration);
                $count = $service->syncProducts();
                $integration->update(['last_synced_at' => now(), 'status' => 'active', 'error_message' => null]);
                session()->flash('flash.banner', "Successfully synced {$count} products from Shopify.");
            } elseif ($integration->type === 'woocommerce') {
                $service = new WooCommerceService($integration);
                $count = $service->syncProducts();
                $integration->update(['last_synced_at' => now(), 'status' => 'active', 'error_message' => null]);
                session()->flash('flash.banner', "Successfully synced {$count} products from WooCommerce.");
            } elseif ($integration->type === 'meta_commerce') {
                \App\Jobs\SyncProductsToMetaJob::dispatch($integration->id);
                $integration->update(['last_synced_at' => now()]);
                session()->flash('flash.banner', "Sync job for Meta Commerce has been dispatched.");
            }
        } catch (\Exception $e) {
            $integration->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            session()->flash('flash.banner', "Sync failed: " . $e->getMessage());
            session()->flash('flash.bannerStyle', 'danger');
        }
    }

    public function delete(Integration $integration)
    {
        $integration->delete();
    }

    public function openDiagnostics($id)
    {
        $this->activeIntegration = Integration::findOrFail($id);
        $this->syncSessions = []; // Reset while loading

        $this->syncSessions = \App\Models\SyncSession::where('integration_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $healthService = app(\App\Services\Integrations\IntegrationHealthService::class);
        $this->healthData = $healthService->checkHealth($this->activeIntegration);

        $this->showDiagnosticsModal = true;
    }

    public function openSettings($id)
    {
        $this->activeIntegration = Integration::findOrFail($id);
        $this->sync_scope = array_merge($this->sync_scope, $this->activeIntegration->settings['sync_scope'] ?? []);
        $this->webhook_secret = $this->activeIntegration->webhook_secret;
        $this->showSettingsModal = true;
    }

    public function saveSettings()
    {
        $this->validate([
            'sync_scope.product_mode' => 'required|in:all,selective',
            'sync_scope.collection_id' => 'nullable|string',
            'sync_scope.category_id' => 'nullable|string',
            'sync_scope.inventory.sync_stock' => 'boolean',
            'sync_scope.inventory.sync_price' => 'boolean',
            'webhook_secret' => 'nullable|string|min:8',
        ]);

        $this->activeIntegration->update([
            'settings' => array_merge($this->activeIntegration->settings ?? [], ['sync_scope' => $this->sync_scope]),
            'webhook_secret' => $this->webhook_secret
        ]);

        $this->showSettingsModal = false;
        session()->flash('flash.banner', "Settings for {$this->activeIntegration->name} updated successfully.");
    }
}

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

    public function render()
    {
        $integrations = Integration::where('team_id', auth()->user()->currentTeam->id)
            ->whereIn('type', ['shopify', 'woocommerce', 'custom'])
            ->get();

        return view('livewire.integrations.ecommerce-integrations', [
            'integrations' => $integrations
        ])->layout('layouts.app');
    }

    public function openConnectModal($type)
    {
        $this->selectedType = $type;
        $this->showConnectModal = true;
        $this->reset(['name', 'domain', 'access_token', 'url', 'consumer_key', 'consumer_secret']);
        $this->name = ucfirst($type) . ' Integration';
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
            if ($integration->type === 'shopify') {
                $service = new ShopifyService($integration);
                $count = $service->syncProducts();
                session()->flash('flash.banner', " synced {$count} products from Shopify.");
            } elseif ($integration->type === 'woocommerce') {
                $service = new WooCommerceService($integration);
                $count = $service->syncProducts();
                session()->flash('flash.banner', " synced {$count} products from WooCommerce.");
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
}

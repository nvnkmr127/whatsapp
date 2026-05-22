<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Team;
use Illuminate\Support\Facades\Http;

class WhatsAppCommerceService
{
    protected $baseUrl;

    protected $team;

    protected $token;

    protected $catalogId;

    public function __construct(?Team $team = null)
    {
        $this->baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com').'/'.config('whatsapp.api_version', 'v21.0');
        if ($team) {
            $this->setTeam($team);
        }
    }

    public function setTeam(Team $team)
    {
        $this->team = $team;
        $this->token = $team->whatsapp_access_token;

        // Resolve catalog_id from the team's meta_commerce integration credentials
        $integration = \App\Models\Integration::where('team_id', $team->id)
            ->where('type', 'meta_commerce')
            ->where('status', 'active')
            ->first();

        $this->catalogId = $integration ? ($integration->credentials['catalog_id'] ?? '') : '';

        return $this;
    }

    public function syncProductToMeta(Product $product)
    {
        // 1. Audit Readiness
        $audit = $product->readiness;
        if (! $audit['is_ready']) {
            $product->update([
                'sync_state' => 'failed',
                'sync_errors' => implode(', ', $audit['issues']),
            ]);
            throw new \Exception('Product not ready for sync: '.implode(', ', $audit['issues']));
        }

        if (! $this->token) {
            throw new \Exception('WhatsApp credentials missing for this team.');
        }

        if (! $this->catalogId) {
            throw new \Exception('Meta catalog ID not configured. Add a Meta Commerce integration with a valid catalog_id.');
        }

        $product->update(['sync_state' => 'syncing']);

        try {
            $payload = [
                'retailer_id' => $product->retailer_id,
                'name' => $product->name,
                'description' => $product->description,
                'availability' => $product->availability,
                'condition' => 'new',
                'price' => (int) ($product->price * 100),
                'currency' => $product->currency,
                'image_url' => $product->image_url,
                'url' => $product->url ?? config('app.url'),
            ];

            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/{$this->catalogId}/products", $payload);

            if ($response->failed()) {
                throw new \Exception('Meta API error: '.$response->json('error.message', 'Unknown error'));
            }

            $product->update([
                'meta_product_id' => $response->json('id'),
                'sync_state' => 'synced',
                'sync_errors' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            $product->update([
                'sync_state' => 'failed',
                'sync_errors' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function sendProductMessage($to, Product $product)
    {
        if (! $this->token || ! $this->catalogId) {
            throw new \Exception('WhatsApp token or catalog ID not configured.');
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'product',
                'body' => ['text' => $product->description ?? $product->name],
                'action' => [
                    'catalog_id' => $this->catalogId,
                    'product_retailer_id' => $product->retailer_id,
                ],
            ],
        ];

        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/messages", $payload);

        if ($response->failed()) {
            throw new \Exception('Failed to send product message: '.$response->json('error.message', 'Unknown error'));
        }

        return $response->json();
    }
}

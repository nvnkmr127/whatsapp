<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCommerceService
{
    protected $baseUrl;
    protected $team;
    protected $token;
    protected $catalogId;

    public function __construct(Team $team = null)
    {
        $this->baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com') . '/' . config('whatsapp.api_version', 'v21.0');
        if ($team) {
            $this->setTeam($team);
        }
    }

    public function setTeam(Team $team)
    {
        $this->team = $team;
        $this->token = $team->whatsapp_access_token;
        // Assuming catalog_id is stored in team metadata or settings. 
        // For now, let's look for it in team config or require it passed.
        $this->catalogId = "123456789"; // Placeholder or fetch from DB
        return $this;
    }

    public function syncProductToMeta(Product $product)
    {
        // 1. Audit Readiness
        $audit = $product->readiness;
        if (!$audit['is_ready']) {
            $product->update([
                'sync_state' => 'failed',
                'sync_errors' => implode(", ", $audit['issues'])
            ]);
            throw new \Exception("Product not ready for sync: " . implode(", ", $audit['issues']));
        }

        if (!$this->token) {
            throw new \Exception("WhatsApp credentials missing for this team.");
        }

        $product->update(['sync_state' => 'syncing']);

        try {
            // Mock API Payload construction
            $payload = [
                'retailer_id' => $product->retailer_id,
                'name' => $product->name,
                'description' => $product->description,
                'availability' => $product->availability,
                'condition' => 'new',
                'price' => (int) ($product->price * 100),
                'currency' => $product->currency,
                'image_url' => $product->image_url,
                'url' => $product->url ?? 'https://example.com',
            ];

            // Simulation of API Call
            // $response = Http::withToken($this->token)->post("{$this->baseUrl}/{$this->catalogId}/products", $payload);

            // For demo purposes, we simulate success
            $product->update([
                'meta_product_id' => 'META_' . strtoupper(bin2hex(random_bytes(4))),
                'sync_state' => 'synced',
                'sync_errors' => null
            ]);

            return true;
        } catch (\Exception $e) {
            $product->update([
                'sync_state' => 'failed',
                'sync_errors' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function sendProductMessage($to, Product $product)
    {
        // messaging_product: whatsapp
        // type: interactive
        // interactive: type: product
        // action: catalog_id, product_retailer_id
    }
}

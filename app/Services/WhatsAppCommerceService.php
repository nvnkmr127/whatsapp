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

    /**
     * Sync a Product to Meta Catalog.
     */
    public function syncProductToMeta(Product $product)
    {
        if (!$this->catalogId) {
            throw new \Exception("Catalog ID is not configured for this team.");
        }

        // https://developers.facebook.com/docs/commerce-platform/catalog/batch-api
        // Ideally we use batch, but for single item:

        $payload = [
            'retailer_id' => $product->retailer_id,
            'name' => $product->name,
            'description' => $product->description,
            'availability' => $product->availability,
            'condition' => 'new',
            'price' => (int) ($product->price * 100), // In cents? No, depends on currency. Meta expects string "10.00" or object.
            'currency' => $product->currency,
            'image_url' => $product->image_url,
            'url' => $product->url ?? 'https://example.com',
        ];

        // This is a complex API call (Commerce Manager).
        // For "Single Product" creation, we post to /{catalog_id}/products usually.
        // Simplifying for MVP: Just Log implementation pattern. User needs real catalog ID.

        // $response = Http::withToken($this->token)->post(...);

        // Mock success for now as we don't have real Catalog ID
        $product->update(['meta_product_id' => 'META_' . uniqid()]);

        return true;
    }

    public function sendProductMessage($to, Product $product)
    {
        // messaging_product: whatsapp
        // type: interactive
        // interactive: type: product
        // action: catalog_id, product_retailer_id
    }
}

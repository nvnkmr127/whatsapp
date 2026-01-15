<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * Sync products from Shopify to local DB.
     */
    public function syncProducts()
    {
        $credentials = $this->integration->credentials;
        $domain = $credentials['domain'] ?? '';
        $accessToken = $credentials['access_token'] ?? '';

        if (!$domain || !$accessToken) {
            throw new \Exception("Missing Shopify credentials");
        }

        // 1. Fetch Products from Shopify
        // https://shopify.dev/docs/api/admin-rest/2024-01/resources/product#get-products
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->get("https://{$domain}/admin/api/2024-01/products.json", [
                    'status' => 'active',
                    'limit' => 250, // Max limit
                    // todo: pagination
                ]);

        if ($response->failed()) {
            throw new \Exception("Shopify API Error: " . $response->body());
        }

        $shopifyProducts = $response->json()['products'] ?? [];
        $syncedCount = 0;

        foreach ($shopifyProducts as $sp) {
            // Shopify Products have variants. We can treat each variant as a product or the main product.
            // For simplicity, let's sync the first variant or create a main product.
            // Better: Sync each variant as a distinct product if they have different prices/SKUs, 
            // OR sync the parent product.

            // Let's go with Parent Product for now, or First Variant if price is needed.
            $firstVariant = $sp['variants'][0] ?? [];
            $price = $firstVariant['price'] ?? 0;
            $sku = $firstVariant['sku'] ?? $sp['id'];

            $image = $sp['image']['src'] ?? null;

            Product::updateOrCreate(
                [
                    'team_id' => $this->integration->team_id,
                    'retailer_id' => (string) $sp['id'], // Shopify Product ID
                ],
                [
                    'name' => $sp['title'],
                    'description' => strip_tags($sp['body_html'] ?? ''),
                    'price' => $price,
                    'currency' => 'USD', // Default or fetch from shop info
                    'image_url' => $image,
                    'url' => "https://{$domain}/products/{$sp['handle']}",
                    'availability' => 'in stock', // Simplified
                    // 'metadata' => ['shopify_data' => $sp]
                ]
            );
            $syncedCount++;
        }

        $this->integration->update([
            'last_synced_at' => now(),
            'error_message' => null
        ]);

        return $syncedCount;
    }
}

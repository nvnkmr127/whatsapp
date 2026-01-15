<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

class WooCommerceService
{
    protected $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    public function syncProducts()
    {
        $credentials = $this->integration->credentials;
        $url = rtrim($credentials['url'] ?? '', '/');
        $consumerKey = $credentials['consumer_key'] ?? '';
        $consumerSecret = $credentials['consumer_secret'] ?? '';

        if (!$url || !$consumerKey || !$consumerSecret) {
            throw new \Exception("Missing WooCommerce credentials");
        }

        // WooCommerce REST API v3
        // Auth is Basic Auth with CK/CS
        $response = Http::withBasicAuth($consumerKey, $consumerSecret)
            ->get("{$url}/wp-json/wc/v3/products", [
                'status' => 'publish',
                'per_page' => 100,
            ]);

        if ($response->failed()) {
            throw new \Exception("WooCommerce API Error: " . $response->body());
        }

        $products = $response->json();
        $syncedCount = 0;

        foreach ($products as $wp) {
            $price = $wp['price'] ?? 0;
            $image = $wp['images'][0]['src'] ?? null;

            Product::updateOrCreate(
                [
                    'team_id' => $this->integration->team_id,
                    'retailer_id' => (string) $wp['id'], // WC Product ID
                ],
                [
                    'name' => $wp['name'],
                    'description' => strip_tags($wp['short_description'] ?? $wp['description']),
                    'price' => $price,
                    'currency' => $wp['currency'] ?? 'USD', // Often WC doesn't send currency in product, but mostly USD/EUR
                    'image_url' => $image,
                    'url' => $wp['permalink'] ?? null,
                    'availability' => ($wp['stock_status'] ?? 'instock') === 'instock' ? 'in stock' : 'out of stock',
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

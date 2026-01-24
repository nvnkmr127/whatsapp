<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Product;
use App\Models\SyncSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ShopifyService
{
    protected $integration;
    protected $healthService;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
        $this->healthService = app(IntegrationHealthService::class);
    }

    /**
     * Sync products from Shopify to local DB.
     */
    public function syncProducts()
    {
        return Cache::lock('sync_integration_' . $this->integration->id, 600)->get(function () {
            $credentials = $this->integration->credentials;
            $domain = $credentials['domain'] ?? '';
            $accessToken = $credentials['access_token'] ?? '';

            if (!$domain || !$accessToken) {
                throw new \Exception("Missing Shopify credentials");
            }

            $lastSync = $this->integration->last_synced_at;
            $session = SyncSession::create([
                'integration_id' => $this->integration->id,
                'type' => 'products',
                'metadata' => ['updated_at_min' => $lastSync?->toIso8601String()],
            ]);

            try {
                $scope = $this->integration->settings['sync_scope'] ?? [];
                $productMode = $scope['product_mode'] ?? 'all';

                $params = [
                    'status' => 'active',
                    'limit' => 250,
                ];

                if ($lastSync) {
                    $params['updated_at_min'] = $lastSync->toIso8601String();
                }

                if ($productMode === 'selective' && !empty($scope['collection_id'])) {
                    $params['collection_id'] = $scope['collection_id'];
                }

                $url = "https://{$domain}/admin/api/2024-01/products.json";
                $syncedCount = 0;

                do {
                    $response = Http::withHeaders([
                        'X-Shopify-Access-Token' => $accessToken,
                    ])->get($url, $params);

                    if ($response->failed()) {
                        throw new \Exception("Shopify API Error: " . $response->status() . " " . $response->body());
                    }

                    $shopifyProducts = $response->json()['products'] ?? [];
                    $session->increment('total_entities', count($shopifyProducts));

                    foreach ($shopifyProducts as $sp) {
                        try {
                            $this->processProduct($sp, $domain, $scope);
                            $session->increment('processed_entities');
                            $syncedCount++;
                        } catch (\Exception $e) {
                            $session->increment('failed_entities');
                            $this->logEntityError($session, 'product', $sp['id'], $e->getMessage());
                        }
                    }

                    $url = $this->getNextPageUrl($response->header('Link'));
                    $params = [];
                } while ($url);

            } catch (\Exception $e) {
                $session->update(['status' => 'failed', 'completed_at' => now()]);
                $this->healthService->reportApiError($this->integration, $e);
                throw $e;
            }

            $session->update([
                'status' => $session->failed_entities > 0 ? 'partially_failed' : 'completed',
                'completed_at' => now(),
            ]);

            $this->integration->update([
                'last_synced_at' => now(),
                'error_message' => null
            ]);

            return $syncedCount;
        });
    }

    protected function processProduct($sp, $domain, $scope)
    {
        $firstVariant = $sp['variants'][0] ?? [];
        $price = $firstVariant['price'] ?? 0;
        $image = $sp['image']['src'] ?? null;

        $updateData = [
            'name' => $sp['title'],
            'description' => strip_tags($sp['body_html'] ?? ''),
            'price' => $price,
            'currency' => 'USD',
            'image_url' => $image,
            'url' => "https://{$domain}/products/{$sp['handle']}",
            'availability' => 'in stock',
            'sync_state' => 'synced',
            'last_sync_error' => null,
            'sync_attempts' => 0,
        ];

        $inventoryScope = $scope['inventory'] ?? ['sync_stock' => true, 'sync_price' => true];
        if (!($inventoryScope['sync_price'] ?? true))
            unset($updateData['price'], $updateData['currency']);
        if (!($inventoryScope['sync_stock'] ?? true))
            unset($updateData['availability']);

        $product = Product::where([
            'team_id' => $this->integration->team_id,
            'retailer_id' => (string) $sp['id'],
        ])->first();

        if ($product) {
            $filteredData = $product->filterIncomingSyncData($updateData);

            // Log if fields were skipped
            $skipped = array_diff_key($updateData, $filteredData);
            if (!empty($skipped)) {
                app(\App\Services\AuditService::class)->log(
                    'product.sync_conflict',
                    "Skipped updating fields: " . implode(', ', array_keys($skipped)) . " due to local locks.",
                    $product
                );
            }

            $product->update($filteredData);
        } else {
            Product::create(array_merge([
                'team_id' => $this->integration->team_id,
                'retailer_id' => (string) $sp['id'],
            ], $updateData));
        }
    }

    protected function logEntityError($session, $type, $id, $message)
    {
        $summary = $session->error_summary ?? [];
        $summary[] = ['type' => $type, 'id' => $id, 'error' => $message, 'at' => now()->toIso8601String()];
        $session->update(['error_summary' => $summary]);

        // Also update product state if found
        Product::where('retailer_id', (string) $id)
            ->where('team_id', $this->integration->team_id)
            ->update([
                'sync_state' => 'failed',
                'last_sync_error' => $message,
            ]);
    }

    protected function getNextPageUrl($linkHeader)
    {
        if (!$linkHeader)
            return null;
        if (preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}

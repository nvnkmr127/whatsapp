<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Product;
use App\Models\SyncSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WooCommerceService
{
    protected $integration;
    protected $healthService;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
        $this->healthService = app(\App\Services\Integrations\IntegrationHealthService::class);
    }

    public function syncProducts()
    {
        return Cache::lock('sync_integration_' . $this->integration->id, 600)->get(function () {
            $credentials = $this->integration->credentials;
            $apiUrl = rtrim($credentials['url'] ?? '', '/');
            $consumerKey = $credentials['consumer_key'] ?? '';
            $consumerSecret = $credentials['consumer_secret'] ?? '';

            if (!$apiUrl || !$consumerKey || !$consumerSecret) {
                throw new \Exception("Missing WooCommerce credentials");
            }

            $lastSync = $this->integration->last_synced_at;
            $session = SyncSession::create([
                'integration_id' => $this->integration->id,
                'type' => 'products',
                'metadata' => ['after' => $lastSync?->toIso8601String()],
            ]);

            try {
                $scope = $this->integration->settings['sync_scope'] ?? [];
                $productMode = $scope['product_mode'] ?? 'all';

                $params = [
                    'status' => 'publish',
                    'per_page' => 100,
                    'orderby' => 'id',
                    'order' => 'asc',
                ];

                if ($lastSync) {
                    $params['after'] = $lastSync->toIso8601String();
                }

                if ($productMode === 'selective' && !empty($scope['category_id'])) {
                    $params['category'] = $scope['category_id'];
                }

                $page = 1;
                $syncedCount = 0;

                do {
                    $params['page'] = $page;
                    $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                        ->get("{$apiUrl}/wp-json/wc/v3/products", $params);

                    if ($response->failed()) {
                        throw new \Exception("WooCommerce API Error: " . $response->status() . " " . $response->body());
                    }

                    $products = $response->json();
                    if (empty($products))
                        break;

                    $session->increment('total_entities', count($products));

                    foreach ($products as $wp) {
                        try {
                            $this->processProduct($wp, $scope);
                            $session->increment('processed_entities');
                            $syncedCount++;
                        } catch (\Exception $e) {
                            $session->increment('failed_entities');
                            $this->logEntityError($session, 'product', $wp['id'], $e->getMessage());
                        }
                    }

                    $totalPages = $response->header('X-WP-TotalPages');
                    $page++;
                } while ($page <= $totalPages);

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

    protected function processProduct($wp, $scope)
    {
        $price = $wp['price'] ?? 0;
        $image = $wp['images'][0]['src'] ?? null;

        $updateData = [
            'name' => $wp['name'],
            'description' => strip_tags($wp['short_description'] ?? $wp['description']),
            'price' => $price,
            'currency' => $wp['currency'] ?? 'USD',
            'image_url' => $image,
            'url' => $wp['permalink'] ?? null,
            'availability' => ($wp['stock_status'] ?? 'instock') === 'instock' ? 'in stock' : 'out of stock',
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
            'retailer_id' => (string) $wp['id'],
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
                'retailer_id' => (string) $wp['id'],
            ], $updateData));
        }
    }

    protected function logEntityError($session, $type, $id, $message)
    {
        $summary = $session->error_summary ?? [];
        $summary[] = ['type' => $type, 'id' => $id, 'error' => $message, 'at' => now()->toIso8601String()];
        $session->update(['error_summary' => $summary]);

        Product::where('retailer_id', (string) $id)
            ->where('team_id', $this->integration->team_id)
            ->update([
                'sync_state' => 'failed',
                'last_sync_error' => $message,
            ]);
    }
}

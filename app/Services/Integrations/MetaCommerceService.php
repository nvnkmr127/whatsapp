<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\Product;
use App\Models\SyncSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MetaCommerceService
{
    protected $integration;
    protected $healthService;
    protected $baseUrl;
    protected $accessToken;
    protected $catalogId;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
        $this->healthService = app(IntegrationHealthService::class);
        $this->baseUrl = 'https://graph.facebook.com/v21.0';

        $credentials = $integration->credentials;
        $this->accessToken = $credentials['access_token'] ?? '';
        $this->catalogId = $credentials['catalog_id'] ?? '';
    }

    /**
     * Sync products from local DB to Meta Catalog.
     */
    public function syncProducts()
    {
        return Cache::lock('sync_integration_' . $this->integration->id, 600)->get(function () {
            if (!$this->accessToken || !$this->catalogId) {
                throw new \Exception("Missing Meta Commerce credentials (Access Token or Catalog ID)");
            }

            $session = SyncSession::create([
                'integration_id' => $this->integration->id,
                'type' => 'products_push',
                'metadata' => ['catalog_id' => $this->catalogId],
                'status' => 'processing'
            ]);

            try {
                // Fetch products belonging to this team that need sync
                $products = Product::where('team_id', $this->integration->team_id)
                    ->where('is_active', true)
                    ->get();

                $session->update(['total_entities' => $products->count()]);
                $syncedCount = 0;

                // Meta Batch API supports up to 50 requests per batch, 
                // but for product feed it's better to use the batch upload endpoint.
                // For simplicity, we'll do them in chunks here.
                foreach ($products->chunk(50) as $chunk) {
                    $batchRequest = [];
                    foreach ($chunk as $product) {
                        $batchRequest[] = $this->formatProductForMeta($product);
                    }

                    try {
                        $this->uploadBatch($batchRequest);

                        foreach ($chunk as $product) {
                            $product->update([
                                'sync_state' => 'synced',
                                'last_external_update_at' => now(),
                                'sync_errors' => null
                            ]);
                            $syncedCount++;
                            $session->increment('processed_entities');
                        }
                    } catch (\Exception $e) {
                        foreach ($chunk as $product) {
                            $product->update([
                                'sync_state' => 'failed',
                                'sync_errors' => $e->getMessage()
                            ]);
                            $session->increment('failed_entities');
                        }
                        Log::error("Meta Sync Batch Failed: " . $e->getMessage());
                    }
                }

                $session->update([
                    'status' => $session->failed_entities > 0 ? 'partially_failed' : 'completed',
                    'completed_at' => now(),
                ]);

            } catch (\Exception $e) {
                $session->update(['status' => 'failed', 'completed_at' => now()]);
                $this->healthService->reportApiError($this->integration, $e);
                throw $e;
            }

            $this->integration->update([
                'last_synced_at' => now(),
                'error_message' => null
            ]);

            return $syncedCount;
        });
    }

    /**
     * Sync a single product to Meta Catalog.
     */
    public function syncSingleProduct(Product $product)
    {
        if (!$this->accessToken || !$this->catalogId) {
            throw new \Exception("Missing Meta Commerce credentials");
        }

        try {
            $request = $this->formatProductForMeta($product);
            $this->uploadBatch([$request]);

            $product->update([
                'sync_state' => 'synced',
                'last_external_update_at' => now(),
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

    protected function formatProductForMeta(Product $product)
    {
        return [
            'method' => 'UPDATE',
            'retailer_id' => (string) $product->retailer_id,
            'data' => [
                'name' => $product->name,
                'description' => $product->description,
                'availability' => $product->availability === 'in stock' ? 'in stock' : 'out of stock',
                'condition' => 'new',
                'price' => (int) ($product->price * 100),
                'currency' => $product->currency ?: 'USD',
                'image_url' => $product->image_url,
                'url' => $product->url ?: 'https://wa.me/' . ($product->team->whatsapp_phone_number ?? ''),
                'brand' => $this->integration->team->name,
            ]
        ];
    }

    protected function uploadBatch(array $requests)
    {
        // Meta Graph API batch upload endpoint
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/{$this->catalogId}/batch", [
                    'allow_upsert' => true,
                    'requests' => $requests
                ]);

        if ($response->failed()) {
            throw new \Exception("Meta API Error: " . $response->status() . " " . $response->body());
        }

        return $response->json();
    }
}

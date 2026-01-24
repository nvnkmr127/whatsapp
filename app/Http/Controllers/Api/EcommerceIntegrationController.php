<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\Product;
use App\Models\SyncSession;
use App\Services\Integrations\IntegrationHealthService;
use App\Services\Integrations\ShopifyService;
use App\Services\Integrations\WooCommerceService;
use App\Services\Integrations\MetaCommerceService;
use App\Jobs\SyncProductsToMetaJob;
use Illuminate\Http\Request;

class EcommerceIntegrationController extends Controller
{
    /**
     * Get integration health and status.
     */
    public function health(Integration $integration, IntegrationHealthService $healthService)
    {
        $health = $healthService->checkHealth($integration);

        return response()->json([
            'integration' => $integration->only(['id', 'name', 'type', 'status', 'health_score', 'last_synced_at']),
            'health' => $health
        ]);
    }

    /**
     * Trigger a manual sync.
     */
    public function sync(Integration $integration)
    {
        if ($integration->status === 'broken' && !request('force')) {
            return response()->json(['error' => 'Integration is marked as broken. Fix credentials first.'], 422);
        }

        try {
            if ($integration->type === 'shopify') {
                $service = new ShopifyService($integration);
                $count = $service->syncProducts();
            } elseif ($integration->type === 'woocommerce') {
                $service = new WooCommerceService($integration);
                $count = $service->syncProducts();
            } elseif ($integration->type === 'meta_commerce') {
                SyncProductsToMetaJob::dispatch($integration->id);
                return response()->json([
                    'message' => 'Sync job dispatched and running in background.',
                ]);
            } else {
                return response()->json(['error' => 'Unsupported integration type for polling.'], 400);
            }

            return response()->json([
                'message' => 'Sync triggered successfully',
                'synced_count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get recent sync sessions.
     */
    public function sessions(Integration $integration)
    {
        $sessions = SyncSession::where('integration_id', $integration->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($sessions);
    }

    /**
     * Toggle field locking for a product.
     */
    public function lockField(Product $product, Request $request)
    {
        $request->validate([
            'field' => 'required|string',
            'lock' => 'required|boolean'
        ]);

        $field = $request->field;
        $shouldLock = $request->lock;
        $lockedFields = $product->locked_fields ?? [];

        if ($shouldLock && !in_array($field, $lockedFields)) {
            $lockedFields[] = $field;
        } elseif (!$shouldLock) {
            $lockedFields = array_values(array_diff($lockedFields, [$field]));
        }

        $product->update(['locked_fields' => $lockedFields]);

        return response()->json([
            'message' => 'Field lock status updated',
            'locked_fields' => $lockedFields
        ]);
    }

    /**
     * Update integration settings.
     */
    public function updateSettings(Integration $integration, Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'webhook_secret' => 'nullable|string'
        ]);

        $integration->update([
            'settings' => array_merge($integration->settings ?? [], $validated['settings']),
            'webhook_secret' => $validated['webhook_secret'] ?? $integration->webhook_secret
        ]);

        return response()->json(['message' => 'Settings updated successfully']);
    }
}

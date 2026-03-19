<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\Product;
use App\Models\SyncSession;
use App\Services\Integrations\IntegrationHealthService;
use App\Services\Integrations\ShopifyService;
use App\Services\Integrations\WooCommerceService;
use App\Jobs\SyncProductsToMetaJob;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;

class EcommerceIntegrationController extends Controller
{
    use StandardApiResponses;

    protected function assertIntegrationAccess(Request $request, Integration $integration)
    {
        $team = $request->user()->currentTeam;
        if (!$team || $integration->team_id !== $team->id) {
            return $this->error('Unauthorized access to integration.', 403, null, 'ERR_FORBIDDEN');
        }
        return null; // All good
    }

    protected function assertProductAccess(Request $request, Product $product)
    {
        $team = $request->user()->currentTeam;
        if (!$team || $product->team_id !== $team->id) {
            return $this->error('Unauthorized access to product.', 403, null, 'ERR_FORBIDDEN');
        }
        return null; // All good
    }

    /**
     * Get integration health and status.
     */
    public function health(Request $request, Integration $integration, IntegrationHealthService $healthService)
    {
        if ($error = $this->assertIntegrationAccess($request, $integration)) {
            return $error;
        }

        $health = $healthService->checkHealth($integration);

        return $this->success([
            'integration' => $integration->only(['id', 'name', 'type', 'status', 'health_score', 'last_synced_at']),
            'health'      => $health
        ], 'Integration health retrieved.');
    }

    /**
     * Trigger a manual sync.
     */
    public function sync(Request $request, Integration $integration)
    {
        if ($error = $this->assertIntegrationAccess($request, $integration)) {
            return $error;
        }

        if ($integration->status === 'broken' && !$request->boolean('force')) {
            return $this->error('Integration is marked as broken. Fix credentials first.', 422, null, 'ERR_INTEGRATION_BROKEN');
        }

        try {
            $count = 0;
            if ($integration->type === 'shopify') {
                $service = new ShopifyService($integration);
                $count = $service->syncProducts();
            } elseif ($integration->type === 'woocommerce') {
                $service = new WooCommerceService($integration);
                $count = $service->syncProducts();
            } elseif ($integration->type === 'meta_commerce') {
                SyncProductsToMetaJob::dispatch($integration->id);
                return $this->success([], 'Sync job dispatched and running in background.');
            } else {
                return $this->error('Unsupported integration type for polling.', 400, null, 'ERR_UNSUPPORTED_TYPE');
            }

            return $this->success([
                'synced_count' => $count
            ], 'Sync triggered successfully.');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, null, 'ERR_SERVER_ERROR');
        }
    }

    /**
     * Get recent sync sessions.
     */
    public function sessions(Request $request, Integration $integration)
    {
        if ($error = $this->assertIntegrationAccess($request, $integration)) {
            return $error;
        }

        $sessions = SyncSession::where('integration_id', $integration->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return $this->success($sessions, 'Recent sync sessions retrieved.');
    }

    /**
     * Toggle field locking for a product.
     */
    public function lockField(Product $product, Request $request)
    {
        if ($error = $this->assertProductAccess($request, $product)) {
            return $error;
        }

        $request->validate([
            'field' => 'required|string',
            'lock'  => 'required|boolean'
        ]);

        $field = $request->field;
        $shouldLock = $request->boolean('lock');
        $lockedFields = $product->locked_fields ?? [];

        if ($shouldLock && !in_array($field, $lockedFields)) {
            $lockedFields[] = $field;
        } elseif (!$shouldLock) {
            $lockedFields = array_values(array_diff($lockedFields, [$field]));
        }

        $product->update(['locked_fields' => $lockedFields]);

        return $this->success([
            'locked_fields' => $lockedFields
        ], 'Field lock status updated.');
    }

    /**
     * Update integration settings.
     */
    public function updateSettings(Integration $integration, Request $request)
    {
        if ($error = $this->assertIntegrationAccess($request, $integration)) {
            return $error;
        }

        $validated = $request->validate([
            'settings'       => 'required|array',
            'webhook_secret' => 'nullable|string'
        ]);

        $integration->update([
            'settings'       => array_merge($integration->settings ?? [], $validated['settings']),
            'webhook_secret' => $validated['webhook_secret'] ?? $integration->webhook_secret
        ]);

        return $this->success([], 'Settings updated successfully.');
    }
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\Product;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;

class ExternalProductController extends Controller
{
    use StandardApiResponses;

    /**
     * Handle incoming product sync from Custom Sites.
     * Expects Header: X-Integration-Token (matches credentials['api_key'])
     */
    public function store(Request $request)
    {
        // 1. Authenticate with X-Integration-Token
        $token = $request->header('X-Integration-Token');
        if (! $token) {
            return $this->error('Missing X-Integration-Token header.', 401, null, 'ERR_AUTH_MISSING');
        }

        // Search for integration by token.
        // Optimization: In a real system, we should hash tokens or store them in a column.
        $integration = Integration::where('type', 'custom')
            ->where('status', 'active')
            ->get()
            ->first(function ($int) use ($token) {
                return ($int->credentials['api_key'] ?? '') === $token;
            });

        if (! $integration) {
            return $this->error('Invalid X-Integration-Token.', 401, null, 'ERR_AUTH_INVALID');
        }

        // 2. Validate payload
        $validated = $request->validate([
            'id' => 'required|string',
            'name' => 'required|string',
            'price' => 'required|numeric',
            'currency' => 'nullable|string',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
            'url' => 'nullable|url',
            'availability' => 'nullable|string',
        ]);

        // 3. Upsert Product
        $product = Product::updateOrCreate(
            [
                'team_id' => $integration->team_id,
                'retailer_id' => $validated['id'],
            ],
            [
                'name' => $validated['name'],
                'price' => $validated['price'],
                'currency' => $validated['currency'] ?? 'USD',
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
                'url' => $validated['url'] ?? null,
                'availability' => $validated['availability'] ?? 'in stock',
            ]
        );

        return $this->success([
            'product_id' => $product->id,
            'retailer_id' => $product->retailer_id,
        ], 'Product synced successfully.');
    }
}

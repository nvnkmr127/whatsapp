<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExternalProductController extends Controller
{
    /**
     * Handle incoming product sync from Custom Sites.
     * Expects Header: X-Integration-Token (matches credentials['api_key'])
     */
    public function store(Request $request)
    {
        // 1. Authenticate with X-Integration-Token
        $token = $request->header('X-Integration-Token');
        if (!$token) {
            return response()->json(['error' => 'Missing X-Integration-Token'], 401);
        }

        $integration = Integration::where('type', 'custom')
            ->where('status', 'active')
            ->get()
            ->first(function ($int) use ($token) {
                // Decrypt credentials to check token. 
                // In production, we should handle this more efficiently than scanning all.
                // But since 'credentials' is cast to encrypted, we can't query it directly easily.
                // For MVP: Iterate. Optimization: Hash the token and store it in a searchable column.
                return ($int->credentials['api_key'] ?? '') === $token;
            });

        if (!$integration) {
            return response()->json(['error' => 'Invalid Token'], 401);
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
            'availability' => 'nullable|string'
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
                'description' => $validated['description'],
                'image_url' => $validated['image_url'],
                'url' => $validated['url'],
                'availability' => $validated['availability'] ?? 'in stock',
            ]
        );

        return response()->json([
            'message' => 'Product synced successfully',
            'product_id' => $product->id
        ]);
    }
}

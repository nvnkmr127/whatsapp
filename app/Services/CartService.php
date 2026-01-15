<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class CartService
{
    /**
     * Get active cart for contact/team context or create new one.
     */
    public function getOrCreateCart(Contact $contact, Team $team, ?string $contextKey = null): Cart
    {
        // 1. Look for existing ACTIVE cart in this context
        $query = Cart::active()
            ->where('team_id', $team->id)
            ->where('contact_id', $contact->id);

        if ($contextKey) {
            $query->where('context_key', $contextKey);
        }

        $cart = $query->first();

        // 2. If exists, refresh expiry and return
        if ($cart) {
            $this->refreshExpiry($cart, $team);
            return $cart;
        }

        // 3. Handle Merge Strategy if cart exists in DIFFERENT context (e.g. global cart vs campaign cart)
        // For simplicity in this version, we treat contexts as isolated unless specified.
        // But if 'cart_merge_strategy' is 'merge', we could look for ANY active cart and claim it.
        $config = $team->commerce_config ?? [];
        $strategy = $config['cart_merge_strategy'] ?? 'merge';

        if ($strategy === 'merge') {
            $existingAnyCart = Cart::active()
                ->where('team_id', $team->id)
                ->where('contact_id', $contact->id)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($existingAnyCart) {
                // "Merge" by adopting this cart and updating context
                // Real merge would be complex (combine items), here we just "Resume" the session
                $existingAnyCart->context_key = $contextKey; // Switch context
                $this->refreshExpiry($existingAnyCart, $team);
                $existingAnyCart->save();
                return $existingAnyCart;
            }
        }

        // 4. Create New Cart
        $cart = Cart::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'context_key' => $contextKey,
            'status' => 'active',
            'currency' => $config['currency'] ?? 'USD',
            'items' => [],
            'total_amount' => 0
        ]);

        $this->refreshExpiry($cart, $team);

        return $cart;
    }

    public function refreshExpiry(Cart $cart, Team $team)
    {
        $config = $team->commerce_config ?? [];
        $minutes = $config['cart_expiry_minutes'] ?? 60;

        $cart->expires_at = now()->addMinutes((int) $minutes);
        $cart->save();
    }

    /**
     * Add product to cart.
     */
    public function addItem(Cart $cart, Product $product, int $quantity = 1)
    {
        // 1. Validate Product Availability
        if ($product->availability !== 'in stock') {
            throw new \Exception("Product {$product->name} is out of stock.");
        }

        // 2. Create Cart Item DTO
        $cartItem = new CartItem(
            $product->id,
            $quantity,
            $product->price,
            ['name' => $product->name, 'image' => $product->image_url]
        );

        // 3. Add to Cart Model
        $cart->addItem($cartItem);

        // 4. Recalculate Totals
        $this->calculateTotal($cart);

        // 5. Refresh Expiry on activity
        if ($cart->team) {
            $this->refreshExpiry($cart, $cart->team);
        }

        return $cart->fresh();
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(Cart $cart, $productId)
    {
        $cart->removeItem($productId);
        $this->calculateTotal($cart);
        return $cart->fresh();
    }

    /**
     * Clear cart.
     */
    public function clearCart(Cart $cart)
    {
        $cart->clear();
        return $cart->fresh();
    }

    /**
     * Calculate and save total amount.
     */
    public function calculateTotal(Cart $cart)
    {
        $total = 0;
        $items = $cart->getCartItems();

        foreach ($items as $item) {
            $total += $item->price * $item->quantity;
        }

        $cart->total_amount = $total;
        $cart->save();

        return $total;
    }
}

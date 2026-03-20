<?php

namespace App\Livewire\Commerce;

use App\Models\Cart;
use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.guest')]
class CartCheckout extends Component
{
    public $cart;
    public $success = false;
    public $order = null;
    
    public $payment_method = 'cod';
    public $shipping_address = '';
    public $notes = '';

    public function mount($uuid)
    {
        $this->cart = Cart::where('uuid', $uuid)->firstOrFail();
        
        if ($this->cart->status !== 'active') {
            abort(404, 'This cart is no longer active.');
        }

        if ($this->cart->expires_at && $this->cart->expires_at->isPast()) {
            abort(404, 'This cart has expired.');
        }
    }

    public function checkout(OrderService $orderService)
    {
        $this->validate([
            'shipping_address' => 'required|string|min:10',
            'payment_method' => 'required|in:cod,stripe,razorpay',
        ]);

        try {
            $paymentDetails = [
                'method' => $this->payment_method,
                'address' => $this->shipping_address,
                'notes' => $this->notes,
            ];

            $this->order = $orderService->createOrderFromCart($this->cart, $paymentDetails);
            $this->success = true;
            
            session()->flash('message', 'Order placed successfully!');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.commerce.cart-checkout');
    }
}

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Your <span class="text-wa-teal">Shopping Cart</span></h1>
            <p class="mt-2 text-sm font-medium text-slate-500">Checkout for {{ $cart->team->name }}</p>
        </div>

        @if($success)
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl p-12 text-center animate-in zoom-in duration-500">
                <div class="w-20 h-20 bg-wa-teal/10 rounded-full flex items-center justify-center mx-auto mb-6 text-wa-teal">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2">Order Confirmed!</h2>
                <p class="text-slate-500 font-medium mb-8 leading-relaxed">Your order <span class="text-slate-900 dark:text-white font-bold">#{{ $order->order_id }}</span> has been successfully placed. We'll send updates via WhatsApp!</p>
                <div class="p-6 bg-slate-50 dark:bg-slate-700/50 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                    <p class="text-xs font-black uppercase text-slate-400 mb-1">Total Amount Paid</p>
                    <p class="text-3xl font-black text-wa-teal">{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 h-full">
                <!-- Summary Section -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-800/50 overflow-hidden">
                        <div class="p-6 border-b border-slate-50 dark:border-slate-700/50">
                            <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Order Items</h3>
                        </div>
                        <div class="p-6 divide-y divide-slate-100 dark:divide-slate-700/50">
                            @foreach($cart->getCartItems() as $item)
                                <div class="py-4 flex gap-4 first:pt-0 last:pb-0">
                                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-700 rounded-xl overflow-hidden shadow-inner flex-shrink-0">
                                        @if($item->metadata['image'] ?? false)
                                            <img src="{{ $item->metadata['image'] }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-slate-300">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-black text-slate-900 dark:text-white truncate uppercase">{{ $item->metadata['name'] }}</p>
                                        <p class="text-xs font-bold text-slate-400">{{ $item->quantity }} x {{ $cart->currency }} {{ number_format($item->price, 2) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ $cart->currency }} {{ number_format($item->price * $item->quantity, 2) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="p-6 bg-slate-50 dark:bg-slate-700/30 border-t border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
                            <span class="text-xs font-black text-slate-500 uppercase">Subtotal</span>
                            <span class="text-2xl font-black text-wa-teal">{{ $cart->currency }} {{ number_format($cart->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Checkout Form -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-slate-800/50 p-8">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight mb-6">Delivery Details</h3>
                        
                        <form wire:submit.prevent="checkout" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase text-slate-400 tracking-widest">Shipping Address</label>
                                <textarea wire:model="shipping_address" rows="3" 
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-700/50 border-none rounded-[1.5rem] text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20"
                                    placeholder="House No, Street, City, Zip..."></textarea>
                                @error('shipping_address') <span class="text-[10px] text-rose-500 font-bold uppercase">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase text-slate-400 tracking-widest">Payment Method</label>
                                <div class="grid grid-cols-1 gap-3">
                                    <label class="relative flex items-center gap-4 p-4 rounded-2xl border-2 transition-all cursor-pointer {{ $payment_method === 'cod' ? 'border-wa-teal bg-wa-teal/5' : 'border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/30' }}">
                                        <input type="radio" wire:model="payment_method" value="cod" class="hidden">
                                        <div class="p-2 {{ $payment_method === 'cod' ? 'bg-wa-teal text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-400' }} rounded-lg">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black uppercase text-slate-900 dark:text-white">Cash on Delivery</p>
                                            <p class="text-[10px] font-bold text-slate-400">Pay when you receive the order.</p>
                                        </div>
                                    </label>
                                    
                                    <!-- Placeholder for other methods based on integration status can be added here -->
                                </div>
                            </div>

                            <div class="pt-4">
                                <button type="submit" 
                                    class="w-full py-4 bg-wa-teal text-slate-900 font-black uppercase tracking-widest text-sm rounded-[1.5rem] shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                                    Confirm Order
                                </button>
                            </div>
                        </form>
                    </div>

                    @if(session()->has('error'))
                        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-500 rounded-2xl flex items-center gap-3">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            <span class="font-bold text-xs uppercase">{{ session('error') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        <div class="mt-12 text-center">
            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Secure Order Processing • Powered by Watxio Commerce</p>
        </div>
    </div>
</div>

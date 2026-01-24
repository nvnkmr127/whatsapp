<div class="space-y-8 pb-20">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Order <span
                        class="text-wa-teal">Fulfillment</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Manage, track, and process customer orders.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden lg:flex items-center gap-6 mr-6 border-r border-slate-100 dark:border-slate-800 pr-6">
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">
                        Revenue</div>
                    <div class="text-lg font-black text-slate-900 dark:text-white leading-none">
                        ${{ number_format($this->orderStats['revenue']) }}</div>
                </div>
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">AOV
                    </div>
                    <div class="text-lg font-black text-wa-teal leading-none">
                        ${{ number_format($this->orderStats['aov'], 2) }}</div>
                </div>
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">
                        Pending</div>
                    <div
                        class="text-lg font-black {{ $this->orderStats['pending'] > 5 ? 'text-rose-500' : 'text-slate-800 dark:text-white' }} leading-none">
                        {{ $this->orderStats['pending'] }}</div>
                </div>
            </div>

            <div class="relative group">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search orders..."
                    class="pl-12 pr-6 py-3 bg-white dark:bg-slate-900 border-none rounded-2xl shadow-xl shadow-slate-900/5 dark:shadow-none text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 w-64 transition-all group-hover:w-80">
                <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-hover:text-wa-teal transition-colors"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="relative">
                <select wire:model.live="statusFilter"
                    class="appearance-none pl-6 pr-12 py-3 bg-white dark:bg-slate-900 border-none rounded-2xl shadow-xl shadow-slate-900/5 dark:shadow-none text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 cursor-pointer transition-all">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="returned">Returned</option>
                </select>
                <svg class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>
    </div>


    <!-- Orders Table/List -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="px-8 py-5 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                            Order ID</th>
                        <th class="px-8 py-5 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                            Customer</th>
                        <th
                            class="px-8 py-5 text-left text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Date</th>
                        <th
                            class="px-8 py-5 text-left text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                            Status</th>
                        <th
                            class="px-8 py-5 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">
                            Total</th>
                        <th class="px-8 py-5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($orders as $order)
                        <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-all cursor-pointer"
                            wire:click="viewDetails({{ $order->id }})">
                            <td class="px-8 py-5">
                                <span
                                    class="text-sm font-black text-slate-900 dark:text-white">#{{ $order->order_id ?? $order->id }}</span>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-wa-teal/10 text-wa-teal rounded-full flex items-center justify-center font-black text-xs">
                                        {{ strtoupper(substr($order->contact->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-black text-slate-900 dark:text-white group-hover:text-wa-teal transition-colors">
                                            {{ $order->contact->name ?? 'Unknown' }}
                                        </span>
                                        <span
                                            class="text-[11px] font-bold text-slate-400">{{ $order->contact->phone_number ?? '' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span
                                    class="text-xs font-bold text-slate-500">{{ $order->created_at->format('M d, Y') }}</span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span
                                    class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-tighter {{ $this->getStatusColor($order->status) }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right font-black text-slate-900 dark:text-white">
                                {{ number_format($order->total_amount, 2) }} <span
                                    class="text-[10px] text-slate-400 ml-1">{{ $order->currency }}</span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <button class="p-2 text-slate-300 group-hover:text-wa-teal transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-3xl text-slate-300">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <span class="text-sm font-black text-slate-400 uppercase tracking-[0.2em]">No Orders
                                        Found</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->count() > 0)
            <div class="px-8 py-6 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <!-- Order Details Slide-In/Modal -->
    <x-dialog-modal wire:model.live="showDetailsModal">
        <x-slot name="title">
            <div class="flex items-center justify-between pointer-events-none">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Order
                        Details</span>
                </div>
                <span
                    class="text-sm font-black text-slate-400">#{{ $viewingOrder->order_id ?? $viewingOrder->id ?? '' }}</span>
            </div>
        </x-slot>

        <x-slot name="content">
            @if($viewingOrder)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 py-4">
                    <!-- Left Column -->
                    <div class="space-y-8">
                        <!-- Customer Info -->
                        <div
                            class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Customer
                                Experience</h4>
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 rounded-2xl flex items-center justify-center font-black text-lg">
                                    {{ strtoupper(substr($viewingOrder->contact->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-base font-black text-slate-900 dark:text-white">
                                        {{ $viewingOrder->contact->name ?? 'Guest User' }}
                                    </div>
                                    <div class="text-[11px] font-bold text-slate-500">
                                        {{ $viewingOrder->contact->phone_number ?? '' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Control -->
                        <div
                            class="p-6 bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 space-y-4">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Pipeline Stage</h4>
                            <div class="flex gap-2">
                                <select wire:model="newStatus"
                                    class="flex-1 px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 cursor-pointer transition-all">
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="returned">Returned</option>
                                </select>
                                <button wire:click="updateStatus"
                                    class="px-6 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-[10px] rounded-xl hover:scale-[1.05] transition-all">
                                    Update
                                </button>
                            </div>
                        </div>

                        <!-- Payment Meta -->
                        <div
                            class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Transaction
                                Intelligence</h4>
                            @if(is_array($viewingOrder->payment_details))
                                <div class="space-y-3">
                                    @foreach($viewingOrder->payment_details as $key => $value)
                                        <div class="flex justify-between items-center text-xs">
                                            <span
                                                class="font-bold text-slate-400 uppercase tracking-tighter">{{ str_replace('_', ' ', $key) }}</span>
                                            <span
                                                class="font-black text-slate-900 dark:text-white">{{ is_string($value) ? $value : json_encode($value) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs font-bold text-slate-400 italic">No metadata available.</span>
                            @endif
                        </div>
                    </div>

                    <!-- Right Column (Items) -->
                    <div
                        class="flex flex-col h-full bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] border border-slate-100 dark:border-slate-800 overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Cart Contents</h4>
                            <span
                                class="text-[10px] font-black text-white bg-slate-900 dark:bg-wa-teal dark:text-slate-900 px-2.5 py-1 rounded-lg">
                                {{ count($viewingOrder->items ?? []) }} Items
                            </span>
                        </div>

                        <div class="flex-1 overflow-y-auto max-h-[400px] p-6 space-y-4">
                            @if(is_array($viewingOrder->items))
                                @foreach($viewingOrder->items as $item)
                                    <div class="flex items-center justify-between group">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-12 h-12 bg-white dark:bg-slate-900 rounded-xl flex items-center justify-center border border-slate-100 dark:border-slate-800">
                                                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-black text-slate-900 dark:text-white">
                                                    {{ $item['product_name'] ?? 'Item' }}
                                                </div>
                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Qty:
                                                    {{ $item['quantity'] ?? 1 }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-sm font-black text-slate-900 dark:text-white">
                                            {{ number_format($item['price'], 2) }}
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <!-- Footer/Total -->
                        <div class="p-6 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total
                                    Value</span>
                                <div class="text-2xl font-black text-slate-900 dark:text-white">
                                    {{ number_format($viewingOrder->total_amount, 2) }} <span
                                        class="text-xs text-wa-teal ml-1">{{ $viewingOrder->currency }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <div class="flex gap-3">
                <button wire:click="$set('showDetailsModal', false)"
                    class="px-6 py-3 bg-slate-50 dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-slate-100 transition-all">
                    Dismiss
                </button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
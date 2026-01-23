<div class="space-y-10 pb-20">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Commerce <span
                        class="text-wa-teal">Insights</span></h1>
            </div>
            <p class="text-slate-500 font-medium">
                Real-time performance overview of your WhatsApp store.
                @if($lastUpdated)
                    <span class="text-xs text-slate-400 ml-2 border-l border-slate-300 pl-2">Updated
                        {{ $lastUpdated->diffForHumans() }}</span>
                @endif
            </p>
        </div>

        <div class="flex items-center gap-4">
            <a href="{{ route('commerce.settings') }}"
                class="p-3 bg-white dark:bg-slate-900 text-slate-400 hover:text-wa-teal rounded-2xl shadow-xl shadow-slate-900/5 dark:shadow-none border border-slate-50 dark:border-slate-800 transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </a>

            <button wire:click="refreshStats" wire:loading.attr="disabled"
                class="flex items-center gap-2 px-6 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 transition-all hover:scale-[1.02] disabled:opacity-75 disabled:cursor-wait">
                <svg wire:loading class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span wire:loading.remove>Refresh Stats</span>
                <span wire:loading>Refreshing...</span>
            </button>
        </div>
    </div>

    @if($isEmpty)
        <!-- Empty State -->
        <div class="relative overflow-hidden rounded-[2.5rem] bg-slate-900 shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-br from-wa-teal/20 to-purple-600/20"></div>
            <div class="absolute -right-20 -top-20 w-96 h-96 bg-wa-teal/20 blur-[100px] rounded-full"></div>

            <div class="relative z-10 p-12 flex flex-col items-center text-center">
                <div
                    class="w-20 h-20 bg-gradient-to-br from-wa-teal to-emerald-400 rounded-3xl flex items-center justify-center mb-6 shadow-xl shadow-wa-teal/20">
                    <svg class="w-10 h-10 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>

                <h2 class="text-3xl font-black text-white mb-4">Start Your Commerce Journey</h2>
                <p class="text-slate-400 text-lg max-w-2xl mb-8 leading-relaxed">
                    Unlock the power of conversational commerce. Sync your catalog, connect your WhatsApp Business account,
                    and start receiving orders directly in chat.
                </p>

                <div class="flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ route('commerce.products') }}"
                        class="px-8 py-4 bg-wa-teal text-slate-900 font-bold rounded-xl shadow-xl shadow-wa-teal/20 hover:scale-105 transition-all">
                        Sync First Product
                    </a>
                    <a href="{{ route('commerce.settings') }}"
                        class="px-8 py-4 bg-slate-800 text-white font-bold rounded-xl border border-slate-700 hover:bg-slate-700 transition-all">
                        Connect WhatsApp
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Revenue Card -->
            <div
                class="bg-slate-900 rounded-[2.5rem] p-8 text-white relative overflow-hidden group shadow-2xl transition-all hover:scale-[1.02]">
                <div
                    class="absolute -right-10 -top-10 w-32 h-32 bg-wa-teal/20 blur-3xl rounded-full group-hover:bg-wa-teal/30 transition-colors">
                </div>

                <div class="relative z-10 flex flex-col h-full justify-between gap-4">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-wa-teal/70">Total Revenue</span>
                        <span class="ml-2 text-[10px] text-slate-500 font-medium">All Time</span>
                    </div>

                    @if($stats['total_revenue'] > 0)
                        <div>
                            <div class="text-4xl font-black tracking-tight">${{ number_format($stats['total_revenue'], 2) }}
                            </div>
                            <div
                                class="flex items-center gap-2 text-xs font-bold pt-2 {{ $trends['revenue'] >= 0 ? 'text-wa-teal' : 'text-red-400' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="{{ $trends['revenue'] >= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' }}" />
                                </svg>
                                <span>{{ number_format(abs($trends['revenue']), 1) }}% vs last month</span>
                            </div>
                        </div>
                    @else
                        <div class="space-y-3">
                            <div class="text-2xl font-bold tracking-tight text-white/90">No sales yet</div>
                            <p class="text-xs text-slate-400 leading-relaxed">Your store is ready. Share your catalog to start
                                selling.</p>
                            <button
                                class="text-xs bg-wa-teal/10 text-wa-teal hover:bg-wa-teal hover:text-slate-900 px-3 py-2 rounded-lg font-bold transition-all flex items-center gap-2 w-fit">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                                Share Store Link
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Orders Card -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 relative group overflow-hidden transition-all hover:border-wa-teal/30">
                <div class="flex flex-col h-full justify-between gap-4 relative z-10">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Orders</span>
                    </div>

                    @if($stats['total_orders'] > 0)
                        <div>
                            <div class="text-4xl font-black tracking-tight text-slate-900 dark:text-white">
                                {{ $stats['total_orders'] }}
                            </div>
                            <div
                                class="flex items-center gap-2 text-xs font-bold pt-2 {{ $trends['orders'] >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="{{ $trends['orders'] >= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' }}" />
                                </svg>
                                <span>{{ number_format(abs($trends['orders']), 1) }}% vs last month</span>
                            </div>
                        </div>
                    @else
                        <div class="space-y-3">
                            <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Awaiting orders</div>
                            <p class="text-xs text-slate-500 leading-relaxed">First detailed analytics will appear here once you
                                receive an order.</p>
                            <a href="{{ route('commerce.orders') }}"
                                class="text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 px-3 py-2 rounded-lg font-bold transition-all inline-flex items-center gap-2 w-fit">
                                Create Test Order
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pending Card -->
            <a href="{{ route('commerce.orders', ['status' => 'pending']) }}"
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 space-y-2 hover:border-wa-orange/30 transition-all flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Pending Orders</span>
                    @if($stats['pending_orders'] > 0)
                        <span class="flex h-2 w-2 rounded-full bg-wa-orange animate-pulse"></span>
                    @else
                        <span class="text-emerald-500 text-xs font-bold">● Live</span>
                    @endif
                </div>

                @if($stats['pending_orders'] > 0)
                    <div>
                        <div class="text-4xl font-black tracking-tight text-wa-orange">{{ $stats['pending_orders'] }}</div>
                        <p class="text-slate-500 text-xs font-bold pt-2">Requires Attention</p>
                    </div>
                @else
                    <div class="py-2">
                        <div class="text-2xl font-bold tracking-tight text-emerald-500 mb-1">All caught up!</div>
                        <p class="text-slate-400 text-xs font-medium">No pending orders right now.</p>
                    </div>
                @endif
            </a>

            <!-- Products Card -->
            <a href="{{ route('commerce.products') }}"
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 space-y-2 hover:border-indigo-500/30 transition-all flex flex-col justify-between">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Stock Items</span>

                @if($stats['total_products'] > 0)
                    <div>
                        <div class="text-4xl font-black tracking-tight text-slate-900 dark:text-white">
                            {{ $stats['total_products'] }}
                        </div>
                        <p class="text-slate-500 text-xs font-bold pt-2">Active in Catalog</p>
                    </div>
                @else
                    <div>
                        <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-gray-400 mb-2">Empty Catalog
                        </div>
                        <p class="text-slate-500 text-xs font-medium mb-3">Add items to start selling.</p>
                        <span
                            class="text-xs bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 px-3 py-1.5 rounded-lg font-bold">
                            + Add Product
                        </span>
                    </div>
                @endif
            </a>
        </div>
    @endif

    <!-- Commerce Funnel -->
    @if(!$isEmpty && isset($funnel['impressions']) && $funnel['impressions'] > 0)
        <div class="mt-8">
            <div class="flex items-center gap-3 mb-6">
                <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400 ml-4">Conversion Funnel</h3>
                <span
                    class="px-2 py-1 bg-wa-teal/10 text-wa-teal text-[10px] font-bold uppercase tracking-wider rounded-md">This
                    Month</span>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 md:p-12 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden">
                <!-- connecting line -->
                <div
                    class="absolute top-1/2 left-10 right-10 h-1 bg-slate-100 dark:bg-slate-800 -translate-y-1/2 hidden md:block">
                </div>

                <div class="relative grid grid-cols-1 md:grid-cols-3 gap-12">
                    <!-- Step 1: Impressions -->
                    <div class="relative group">
                        <div
                            class="absolute inset-0 bg-white dark:bg-slate-900 rounded-3xl translate-y-2 translate-x-2 border border-slate-100 dark:border-slate-800 transition-transform group-hover:translate-x-3 group-hover:translate-y-3">
                        </div>
                        <div
                            class="relative bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-lg flex flex-col items-center text-center z-10 transition-transform hover:-translate-y-1">
                            <div
                                class="w-12 h-12 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <div class="text-3xl font-black text-slate-900 dark:text-white mb-1">
                                {{ number_format($funnel['impressions']) }}
                            </div>
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Catalog Views</div>
                        </div>
                    </div>

                    <!-- Conversion Rate 1 -->
                    <div
                        class="absolute left-[33%] top-1/2 -translate-x-1/2 -translate-y-1/2 z-20 hidden md:flex flex-col items-center">
                        <div
                            class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black py-1 px-3 rounded-full shadow-lg">
                            {{ number_format($funnel['rates']['cart_rate'], 1) }}%
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 mt-1 uppercase">Add to Cart</span>
                    </div>

                    <!-- Step 2: Carts -->
                    <div class="relative group">
                        <div
                            class="absolute inset-0 bg-white dark:bg-slate-900 rounded-3xl translate-y-2 translate-x-2 border border-slate-100 dark:border-slate-800 transition-transform group-hover:translate-x-3 group-hover:translate-y-3">
                        </div>
                        <div
                            class="relative bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-lg flex flex-col items-center text-center z-10 transition-transform hover:-translate-y-1">
                            <div
                                class="w-12 h-12 bg-purple-50 dark:bg-purple-500/10 text-purple-500 rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="text-3xl font-black text-slate-900 dark:text-white mb-1">
                                {{ number_format($funnel['carts']) }}
                            </div>
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Carts</div>
                        </div>
                    </div>

                    <!-- Conversion Rate 2 -->
                    <div
                        class="absolute right-[33%] top-1/2 translate-x-1/2 -translate-y-1/2 z-20 hidden md:flex flex-col items-center">
                        <div
                            class="bg-wa-teal text-slate-900 text-xs font-black py-1 px-3 rounded-full shadow-lg shadow-wa-teal/20">
                            {{ number_format($funnel['rates']['conversion_rate'], 1) }}%
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 mt-1 uppercase">Converted</span>
                    </div>

                    <!-- Step 3: Orders -->
                    <div class="relative group">
                        <div
                            class="absolute inset-0 bg-white dark:bg-slate-900 rounded-3xl translate-y-2 translate-x-2 border border-slate-100 dark:border-slate-800 transition-transform group-hover:translate-x-3 group-hover:translate-y-3">
                        </div>
                        <div
                            class="relative bg-white dark:bg-slate-900 p-6 rounded-3xl border border-wa-teal/30 shadow-lg flex flex-col items-center text-center z-10 transition-transform hover:-translate-y-1 ring-4 ring-wa-teal/5">
                            <div
                                class="w-12 h-12 bg-wa-teal/10 text-wa-teal rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-3xl font-black text-slate-900 dark:text-white mb-1">
                                {{ number_format($funnel['orders']) }}
                            </div>
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Completed Orders</div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 text-center">
                    <p class="text-sm text-slate-500 font-medium">
                        Total Conversion Rate: <span
                            class="text-slate-900 dark:text-white font-black">{{ number_format($funnel['rates']['global_rate'], 1) }}%</span>
                        from view to purchase.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Command Center -->
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400 ml-4">Operations Center</h3>
            @if(isset($operational) && ($operational['out_of_stock'] > 0 || $operational['ready_to_ship'] > 0))
                <span class="flex h-2 w-2 rounded-full bg-wa-orange animate-pulse"></span>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Order Management -->
            <a href="{{ route('commerce.orders') }}"
                class="group relative bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 hover:border-wa-teal/30 transition-all duration-500">
                <div class="flex items-center justify-between mb-6">
                    <div
                        class="p-4 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 rounded-2xl group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>

                    @if(isset($operational['ready_to_ship']) && $operational['ready_to_ship'] > 0)
                        <div
                            class="px-3 py-1 bg-wa-orange/10 text-wa-orange text-[10px] font-black uppercase tracking-wider rounded-full flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-wa-orange animate-pulse"></span>
                            {{ $operational['ready_to_ship'] }} To Ship
                        </div>
                    @else
                        <div
                            class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-wider rounded-full">
                            All Clear
                        </div>
                    @endif
                </div>

                <h4 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2">Order
                    Manager</h4>
                <div class="flex flex-col gap-1">
                    <p class="text-slate-500 text-sm font-medium leading-relaxed">Process shipments and manage returns.
                    </p>
                    @if(isset($operational['returns']) && $operational['returns'] > 0)
                        <span class="text-xs text-red-400 font-bold mt-2">{{ $operational['returns'] }} Returns
                            Pending</span>
                    @endif
                </div>
            </a>

            <!-- Product Management -->
            <a href="{{ route('commerce.products') }}"
                class="group relative bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 hover:border-wa-teal/30 transition-all duration-500">
                <div class="flex items-center justify-between mb-6">
                    <div
                        class="p-4 bg-pink-50 dark:bg-pink-500/10 text-pink-600 rounded-2xl group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>

                    @if(isset($operational['out_of_stock']) && $operational['out_of_stock'] > 0)
                        <div
                            class="px-3 py-1 bg-red-50 text-red-500 text-[10px] font-black uppercase tracking-wider rounded-full flex items-center gap-2">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Restock Needed
                        </div>
                    @else
                        <div
                            class="px-3 py-1 bg-slate-100 text-slate-500 text-[10px] font-black uppercase tracking-wider rounded-full">
                            Synced
                        </div>
                    @endif
                </div>

                <h4 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2">Product
                    Catalog</h4>
                <div class="flex flex-col gap-1">
                    <p class="text-slate-500 text-sm font-medium leading-relaxed">Manage inventory and pricing.</p>
                    @if(isset($operational['out_of_stock']) && $operational['out_of_stock'] > 0)
                        <span class="text-xs text-red-400 font-bold mt-2">{{ $operational['out_of_stock'] }} Items Out of
                            Stock</span>
                    @endif
                </div>
            </a>

            <!-- AI Engine -->
            <a href="{{ route('settings.ai') }}"
                class="group relative bg-slate-900 dark:bg-slate-800 p-8 rounded-[2.5rem] shadow-2xl overflow-hidden hover:scale-[1.02] transition-all duration-500">
                <div class="absolute -right-20 -bottom-20 w-60 h-60 bg-wa-teal/10 blur-[100px] rounded-full"></div>

                <div class="relative z-10 flex flex-col h-full justify-between">
                    <div class="flex items-center justify-between mb-6">
                        <div class="p-4 bg-wa-teal/20 text-wa-teal rounded-2xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        @if(isset($operational['ai']['active']) && $operational['ai']['active'])
                            <div
                                class="px-3 py-1 bg-gradient-to-r from-wa-teal to-emerald-400 text-slate-900 text-[10px] font-black uppercase tracking-tighter rounded-full shadow-lg shadow-wa-teal/30 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 bg-slate-900 rounded-full animate-pulse"></span>
                                Active
                            </div>
                        @else
                            <div
                                class="px-3 py-1 bg-slate-700 text-slate-300 text-[10px] font-black uppercase tracking-tighter rounded-full">
                                Inactive
                            </div>
                        @endif
                    </div>

                    <div>
                        <h4 class="text-xl font-black text-white uppercase tracking-tight mb-2">AI Shop Assistant</h4>

                        @if(isset($operational['ai']['active']) && $operational['ai']['active'])
                            <div class="grid grid-cols-2 gap-4 mt-4 mb-4">
                                <div>
                                    <div class="text-2xl font-black text-wa-teal">
                                        {{ number_format($operational['ai']['replies']) }}</div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Replies Sent
                                    </div>
                                </div>
                                <div>
                                    <div class="text-2xl font-black text-white">{{ $operational['ai']['hours_saved'] }}h
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Time Saved
                                    </div>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-2 text-wa-teal text-xs font-black uppercase tracking-widest group-hover:gap-4 transition-all">
                                View Activity
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </span>
                        @else
                            <p class="text-slate-400 text-sm font-medium leading-relaxed mb-6">Automate customer support and
                                recover abandoned carts.</p>
                            <span
                                class="inline-flex items-center gap-2 text-white text-xs font-black uppercase tracking-widest group-hover:gap-4 transition-all">
                                Enable Auto-Replies
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </span>
                        @endif
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
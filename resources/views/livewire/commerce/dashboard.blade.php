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
            <p class="text-slate-500 font-medium">Real-time performance overview of your WhatsApp store.</p>
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

            <button
                class="flex items-center gap-2 px-6 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 transition-all hover:scale-[1.02]">
                Refresh Stats
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Revenue Card -->
        <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white relative overflow-hidden group shadow-2xl">
            <div
                class="absolute -right-10 -top-10 w-32 h-32 bg-wa-teal/20 blur-3xl rounded-full group-hover:bg-wa-teal/30 transition-colors">
            </div>
            <div class="relative z-10 space-y-2">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-wa-teal/70">Total Revenue</span>
                <div class="text-4xl font-black tracking-tight">${{ number_format($stats['total_revenue'], 2) }}</div>
                <div class="flex items-center gap-2 text-wa-green text-xs font-bold pt-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    <span>Store Performance</span>
                </div>
            </div>
        </div>

        <!-- Orders Card -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 space-y-2">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Orders</span>
            <div class="text-4xl font-black tracking-tight text-slate-900 dark:text-white">{{ $stats['total_orders'] }}
            </div>
            <p class="text-slate-500 text-xs font-bold pt-2">Across all campaigns</p>
        </div>

        <!-- Pending Card -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 space-y-2">
            <div class="flex justify-between items-start">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Pending Orders</span>
                <span class="flex h-2 w-2 rounded-full bg-wa-orange animate-pulse"></span>
            </div>
            <div class="text-4xl font-black tracking-tight text-wa-orange">{{ $stats['pending_orders'] }}</div>
            <p class="text-slate-500 text-xs font-bold pt-2">Requires Attention</p>
        </div>

        <!-- Products Card -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 space-y-2">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Stock Items</span>
            <div class="text-4xl font-black tracking-tight text-slate-900 dark:text-white">
                {{ $stats['total_products'] }}
            </div>
            <p class="text-slate-500 text-xs font-bold pt-2">Active in Catalog</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="space-y-6">
        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400 ml-4">Command Center</h3>
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
                    <div
                        class="p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </div>
                </div>
                <h4 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2">Order
                    Manager</h4>
                <p class="text-slate-500 text-sm font-medium leading-relaxed">Process shipments, track fulfillment, and
                    manage cancellations.</p>
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
                    <div
                        class="p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </div>
                </div>
                <h4 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2">Product
                    Catalog</h4>
                <p class="text-slate-500 text-sm font-medium leading-relaxed">Sync inventory, update pricing, and curate
                    your WhatsApp shop vitals.</p>
            </a>

            <!-- AI Engine -->
            <div
                class="group relative bg-slate-900 dark:bg-slate-800 p-8 rounded-[2.5rem] shadow-2xl overflow-hidden hover:scale-[1.02] transition-all duration-500">
                <div class="absolute -right-20 -bottom-20 w-60 h-60 bg-wa-teal/10 blur-[100px] rounded-full"></div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="p-4 bg-wa-teal/20 text-wa-teal rounded-2xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div
                            class="px-3 py-1 bg-wa-teal text-slate-900 text-[10px] font-black uppercase tracking-tighter rounded-full shadow-lg shadow-wa-teal/30">
                            Active</div>
                    </div>
                    <h4 class="text-xl font-black text-white uppercase tracking-tight mb-2">AI Shop Assistant</h4>
                    <p class="text-slate-400 text-sm font-medium leading-relaxed mb-6">Autonomous recommendation engine
                        handling customer inquiries.</p>

                    <a href="{{ route('settings.ai') }}"
                        class="inline-flex items-center gap-2 text-wa-teal text-xs font-black uppercase tracking-widest group-hover:gap-4 transition-all">
                        Configure Brain
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-blue-100 text-wa-teal rounded-lg dark:bg-blue-500/10 dark:wa-teal">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Analytics &
                    <span class="text-wa-teal dark:wa-teal">Billing</span>
                </h1>
            </div>
            <p class="text-slate-500 font-medium">Keep track of your stats, usage, and money.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Time</label>
                <select wire:model.live="dateRange"
                    class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200">
                    <option value="7">7d</option>
                    <option value="30">30d</option>
                    <option value="90">90d</option>
                </select>
            </div>

            <div class="hidden md:flex flex-col items-end mr-4">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Last Updated</span>
                <span class="text-xs font-bold text-wa-teal flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-wa-teal animate-pulse"></span>
                    Synced: {{ $lastUpdated->diffForHumans() }}
                </span>
            </div>

            <button wire:click="refreshData" wire:loading.class="animate-spin"
                class="p-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>

            <button wire:click="exportTransactions"
                class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 font-bold text-xs uppercase tracking-widest rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download Billing
            </button>
            <button wire:click="toggleSchedule"
                class="flex items-center gap-2 px-6 py-3 {{ $isScheduled ? 'bg-wa-teal text-white' : 'bg-slate-900 dark:bg-white text-white dark:text-slate-900' }} font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl transition-all hover:scale-[1.02] active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ $isScheduled ? 'Scheduled' : 'Schedule' }}
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <!-- Balance Card -->
        <div
            class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform text-blue-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Wallet Balance</h3>
                <div class="flex items-baseline gap-1">
                    <span
                        class="text-3xl font-black text-slate-900 dark:text-white">{{ get_setting('currency_symbol', '$') }}{{ number_format($wallet->balance, 2) }}</span>
                </div>
                <button
                    class="mt-4 text-xs font-bold text-wa-teal dark:wa-teal hover:underline flex items-center gap-1">
                    Add Funds
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Sent -->
        <div
            class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform text-wa-teal">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Sent (30d)</h3>
                <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($msgSent) }}</div>
                <p class="mt-4 text-[10px] font-bold text-wa-teal uppercase tracking-widest">Sent Messages</p>
            </div>
        </div>

        <!-- Messages Received -->
        <div
            class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none relative overflow-hidden group">
            <div
                class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform text-purple-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Received (30d)</h3>
                <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($msgReceived) }}</div>
                <p class="mt-4 text-[10px] font-bold text-purple-500 uppercase tracking-widest">Received Messages</p>
            </div>
        </div>

        <!-- Lead Capture Card -->
        <div
            class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none relative overflow-hidden group">
            <div
                class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform text-indigo-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Lead Capture (All
                    Time)</h3>
                <div class="flex items-baseline gap-2">
                    <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($qrConversions) }}
                    </div>
                    <div class="text-xs font-bold text-slate-400">/ {{ number_format($qrScans) }} scans</div>
                </div>
                <p class="mt-4 text-[10px] font-bold text-indigo-500 uppercase tracking-widest">QR Code Scans</p>
            </div>
        </div>

        <!-- Issues Fixed -->
        <div
            class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none relative overflow-hidden group">
            <div
                class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-110 transition-transform text-orange-500">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Issues Fixed</h3>
                <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($ticketsResolved) }}
                </div>
                <p class="mt-4 text-[10px] font-bold text-orange-500 uppercase tracking-widest">Customer Support</p>
            </div>
        </div>
    </div>


    <!-- Official Meta Insights -->
    @if(!empty($metaAnalytics))
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 sm:p-10 shadow-xl border border-slate-100 dark:border-slate-800 space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Official <span class="text-wa-teal">Meta Insights</span>
                    </h3>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                        Direct billing and usage data from WhatsApp
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800/50">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                        Connected
                    </span>
                    <a href="https://business.facebook.com/" target="_blank"
                        class="text-xs font-black uppercase tracking-widest text-wa-teal hover:text-blue-700 transition-colors flex items-center gap-1">
                        Meta Manager
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 012 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @php
                    $firstDataPoint = isset($metaAnalytics['data'][0]['data_points']) ? $metaAnalytics['data'][0]['data_points'] : [];
                @endphp

                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-transparent hover:border-wa-teal/20 transition-all">
                    <dt class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Chat Cost</dt>
                    <dd class="text-3xl font-black text-slate-900 dark:text-white">
                        @if(!empty($firstDataPoint))
                            <span class="text-sm font-bold text-slate-400 uppercase">See JSON Details</span>
                        @else
                            <span class="text-slate-400">0.00</span>
                        @endif
                    </dd>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-transparent hover:border-wa-teal/20 transition-all">
                    <dt class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Time Period</dt>
                    <dd class="text-3xl font-black text-slate-900 dark:text-white uppercase">Daily</dd>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-6 border border-transparent hover:border-wa-teal/20 transition-all">
                    <dt class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Records</dt>
                    <dd class="text-3xl font-black text-slate-900 dark:text-white">{{ count($metaAnalytics['data'] ?? []) }}</dd>
                </div>
            </div>

            <div class="pt-4" x-data="{ open: false }">
                <button @click="open = !open" type="button"
                    class="group inline-flex items-center text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-wa-teal transition-colors">
                    <svg :class="{'rotate-90': open}"
                        class="mr-2 h-4 w-4 transform transition-transform"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span>View Data Response</span>
                </button>
                <div x-show="open"
                    class="mt-4 bg-slate-900 rounded-2xl p-6 overflow-x-auto max-h-96 custom-scrollbar"
                    style="display: none;">
                    <pre class="text-[11px] text-emerald-400 font-mono leading-relaxed">{{ json_encode($metaAnalytics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>
    @endif

    <!-- Detailed View -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Message Velocity Chart -->
        <div
            class="lg:col-span-3 bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 sm:p-10 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 blur-3xl rounded-full -mr-32 -mt-32"></div>

            <div class="relative">
                <div class="flex items-center justify-between mb-10">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Message
                            <span class="text-wa-teal">Speed</span>
                        </h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Live message
                            tracking</p>
                    </div>
                    <div class="flex items-center gap-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-wa-teal shadow-lg shadow-wa-teal/20"></span> Sent
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-wa-teal shadow-lg shadow-wa-teal/20"></span> Received
                        </div>
                    </div>
                </div>

                <div class="relative h-[350px] w-full" wire:ignore>
                    <x-app-chart :data="$chartData" height="350px" />
                </div>
            </div>
        </div>

        <div class="lg:col-span-3">
            <livewire:analytics.module-insights />
        </div>

        <div class="lg:col-span-3">
            <livewire:analytics.campaign-funnel />
        </div>

        <!-- Billing History -->
        <div
            class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between gap-3">
                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Billing <span
                        class="text-wa-teal">History</span></h3>
                <div class="flex items-center gap-2">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Transactions</div>
                    <select wire:model.live="transactionsPerPage"
                        class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <div class="max-h-[30rem] overflow-y-auto">
                <table class="w-full text-left min-w-[700px]">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800/50">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Date
                            </th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Type
                            </th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Amount
                            </th>
                            <th
                                class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                                Invoice</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                        @forelse($transactions as $txn)
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                        {{ $txn->created_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">{{ $txn->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded text-slate-600 dark:text-slate-400">
                                        {{ ucfirst(str_replace('_', ' ', $txn->type)) }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-sm font-black {{ $txn->amount < 0 ? 'text-rose-500' : 'text-wa-teal' }}">
                                        {{ $txn->amount < 0 ? '-' : '+' }}{{ get_setting('currency_symbol', '$') }}{{ number_format(abs($txn->amount), 2) }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    @if($txn->invoice_number)
                                        <button class="text-blue-500 hover:text-blue-700 transition-colors">
                                            <svg class="w-5 h-5 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-10 text-center text-sm font-semibold text-slate-400">
                                    No transactions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            <div class="px-8 py-4 border-t border-slate-50 dark:border-slate-800 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                    Showing {{ $transactions->firstItem() ?? 0 }}-{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} transactions
                </div>
                <div>
                    {{ $transactions->onEachSide(1)->links() }}
                </div>
            </div>
        </div>

        <!-- Performance Insights -->
        <div class="space-y-8">
            <div
                class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none">
                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight mb-4">Insights
                </h3>
                <div class="space-y-6">
                    <div
                        class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-2xl border border-blue-100/50 dark:border-blue-800/30">
                        <div class="text-xs font-black text-wa-teal dark:wa-teal uppercase tracking-widest mb-1">
                            Coming Soon</div>
                        <p class="text-sm text-blue-800 dark:text-blue-300 leading-relaxed font-medium">Agent
                            stats, response time, and happiness scores will appear here.</p>
                    </div>
                </div>
            </div>

            <!-- Support Status -->
            <div class="bg-slate-900 dark:bg-white p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-10">
                    <svg class="w-32 h-32 text-white dark:text-slate-900" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                    </svg>
                </div>
                <h3 class="text-white dark:text-slate-900 text-lg font-black uppercase tracking-tight mb-2">Need Help?
                </h3>
                <p class="text-slate-400 dark:text-slate-500 text-sm font-medium mb-6 leading-relaxed">Contact our
                    billing team if you see any discrepancies in your wallet balance or invoices.</p>
                <button
                    class="w-full py-3 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black uppercase tracking-widest text-[10px] rounded-xl hover:scale-[1.02] transition-transform">Contact
                    Billing</button>
            </div>
        </div>
    </div>

    <!-- Webhook Delivery Report (Table) -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 sm:p-10 shadow-xl border border-slate-50 dark:border-slate-800 space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                    Message <span class="text-wa-teal">Delivery Report</span>
                </h3>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                    Sent message statuses for last {{ $this->dateRange }} days
                </p>
                <div class="mt-2 flex items-center gap-3 text-[10px] font-black uppercase tracking-widest">
                    <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                        Delivery Rate: {{ number_format($webhookSummary['delivery_rate'], 1) }}%
                    </span>
                    <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                        Read Rate: {{ number_format($webhookSummary['read_rate'], 1) }}%
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Status</label>
                    <select wire:model.live="webhookStatusFilter"
                        class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200">
                        <option value="all">All</option>
                        <option value="sent">Sent</option>
                        <option value="delivered">Delivered</option>
                        <option value="read">Read</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Contact</label>
                    <input type="text" wire:model.live.debounce.300ms="webhookContactFilter" placeholder="Name, number, email"
                        class="w-44 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 placeholder:text-slate-400">
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="webhookSearch" placeholder="Message ID or error"
                        class="w-44 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 placeholder:text-slate-400">
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">From</label>
                    <input type="date" wire:model.live="webhookFromDate"
                        class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200">
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">To</label>
                    <input type="date" wire:model.live="webhookToDate"
                        class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200">
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Rows</label>
                    <select wire:model.live="webhookPerPage"
                        class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <button wire:click="exportWebhookReport"
                    class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 font-bold text-xs uppercase tracking-widest rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download Delivery Report
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="rounded-2xl border border-slate-100 dark:border-slate-800 p-5 bg-slate-50/70 dark:bg-slate-800/30">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Sent</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($webhookSummary['sent']) }}</p>
            </div>
            <div class="rounded-2xl border border-blue-100 dark:border-blue-900/30 p-5 bg-blue-50/70 dark:bg-blue-900/10">
                <p class="text-[10px] font-black uppercase tracking-widest text-blue-500">Delivered</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($webhookSummary['delivered']) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 dark:border-emerald-900/30 p-5 bg-emerald-50/70 dark:bg-emerald-900/10">
                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500">Read</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($webhookSummary['read']) }}</p>
            </div>
            <div class="rounded-2xl border border-rose-100 dark:border-rose-900/30 p-5 bg-rose-50/70 dark:bg-rose-900/10">
                <p class="text-[10px] font-black uppercase tracking-widest text-rose-500">Failed</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($webhookSummary['failed']) }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
            <div class="overflow-x-auto">
            <div class="max-h-[30rem] overflow-y-auto">
            <table class="w-full text-left min-w-[900px]">
                <thead class="bg-slate-50 dark:bg-slate-800/40">
                    <tr>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Time</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Contact</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Number</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Message ID</th>
                        <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($webhookDetails as $message)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-5 py-4 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                {{ $message->created_at?->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-5 py-4 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                {{ $message->contact?->name ?: 'Unknown' }}
                                @if($message->contact?->email)
                                    <div class="text-[10px] text-slate-400">{{ $message->contact->email }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                {{ $message->contact?->phone_number ?: '-' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded
                                {{ $message->status === 'failed' ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/20 dark:text-rose-300' : '' }}
                                {{ $message->status === 'read' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-300' : '' }}
                                {{ $message->status === 'delivered' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-300' : '' }}
                                {{ $message->status === 'sent' ? 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200' : '' }}">
                                    {{ $message->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-[11px] font-mono text-slate-500 dark:text-slate-400 max-w-[240px] truncate">
                                {{ $message->whatsapp_message_id ?: '-' }}
                            </td>
                            <td class="px-5 py-4 text-xs text-rose-500 max-w-[260px] truncate">
                                {{ $message->error_message ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm font-semibold text-slate-400">
                                No message activity for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            </div>
            <div class="px-5 py-4 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                    Showing {{ $webhookDetails->firstItem() ?? 0 }}-{{ $webhookDetails->lastItem() ?? 0 }} of {{ $webhookDetails->total() }} messages
                </div>
                <div>
                    {{ $webhookDetails->onEachSide(1)->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
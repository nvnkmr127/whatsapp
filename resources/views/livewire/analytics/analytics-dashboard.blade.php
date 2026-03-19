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
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-8 mb-10">
            <div class="flex items-start gap-5">
                <div class="p-4 bg-wa-teal dark:bg-wa-teal/10 text-white dark:text-wa-teal rounded-[1.5rem] shadow-xl shadow-wa-teal/20 hidden sm:flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight leading-none mb-2">
                        Message <span class="text-wa-teal">Delivery Report</span>
                    </h3>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-wa-teal animate-pulse"></span>
                        History tracking for last {{ $this->dateRange }} days
                    </p>
                    
                    <div class="mt-4 flex items-center gap-3">
                        <div class="bg-blue-50/50 dark:bg-blue-500/5 border border-blue-100 dark:border-blue-500/10 px-5 py-3 rounded-2xl group transition-all shrink-0">
                            <div class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1 group-hover:text-blue-500 transition-colors">Delivery Rate</div>
                            <div class="text-xl font-black text-blue-600 dark:text-blue-400 leading-none">{{ number_format($webhookSummary['delivery_rate'], 1) }}%</div>
                        </div>
                        <div class="bg-emerald-50/50 dark:bg-emerald-500/5 border border-emerald-100 dark:border-emerald-500/10 px-5 py-3 rounded-2xl group transition-all shrink-0">
                            <div class="text-[9px] font-black text-emerald-400 uppercase tracking-widest mb-1 group-hover:text-emerald-500 transition-colors">Read Rate</div>
                            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 leading-none">{{ number_format($webhookSummary['read_rate'], 1) }}%</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button wire:click="exportWebhookReport" 
                                class="flex flex-col items-center justify-center gap-1.5 px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl shadow-xl shadow-slate-900/10 hover:scale-[1.02] active:scale-95 transition-all">
                                <div class="text-[8px] font-black uppercase tracking-[0.2em] opacity-70">Download</div>
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] flex items-center gap-2">
                                    REPT
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </div>
                            </button>

                            <button wire:click="exportFilteredContacts" 
                                class="flex flex-col items-center justify-center gap-1.5 px-6 py-3 bg-emerald-600 dark:bg-emerald-500 text-white rounded-2xl shadow-xl shadow-emerald-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                                <div class="text-[8px] font-black uppercase tracking-[0.2em] opacity-70">Extract</div>
                                <div class="text-[10px] font-black uppercase tracking-[0.2em] flex items-center gap-2">
                                    LIST
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Redesigned Filter Toolbar -->
            <div class="flex flex-col gap-4 w-full lg:w-auto">
                <div class="bg-slate-50/50 dark:bg-slate-800/40 p-1.5 rounded-[2rem] border border-slate-100 dark:border-slate-800/60 flex flex-wrap items-center gap-2 shadow-sm">
                    <!-- Status Dropdown -->
                    <div class="relative group">
                        <select wire:model.live="webhookStatusFilter"
                            class="appearance-none pl-5 pr-12 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[1.5rem] text-[11px] font-black uppercase tracking-widest text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-wa-teal transition-all cursor-pointer shadow-sm">
                            <option value="all">Statuses: All</option>
                            <option value="sent">Status: Sent</option>
                            <option value="delivered">Status: Delivered</option>
                            <option value="read">Status: Read</option>
                            <option value="failed">Status: Failed</option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <!-- Page Rows Dropdown -->
                    <div class="relative group">
                        <select wire:model.live="webhookPerPage"
                            class="appearance-none pl-5 pr-12 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[1.5rem] text-[11px] font-black uppercase tracking-widest text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-wa-teal transition-all cursor-pointer shadow-sm">
                            <option value="10">10 Rows</option>
                            <option value="15">15 Rows</option>
                            <option value="25">25 Rows</option>
                            <option value="50">50 Rows</option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <!-- Date Inputs -->
                    <div class="flex items-center gap-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[1.5rem] px-3 shadow-sm py-1">
                        <div class="text-[9px] font-black text-slate-300 uppercase tracking-widest px-2">From</div>
                        <input type="date" wire:model.live="webhookFromDate"
                            class="bg-transparent border-none text-[11px] font-black p-2 text-slate-600 dark:text-slate-300 focus:ring-0 w-32">
                        <div class="text-[9px] font-black text-slate-300 uppercase tracking-widest px-2">To</div>
                        <input type="date" wire:model.live="webhookToDate"
                            class="bg-transparent border-none text-[11px] font-black p-2 text-slate-600 dark:text-slate-300 focus:ring-0 w-32">
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="relative group flex-1 w-full">
                        <input type="text" wire:model.live.debounce.300ms="webhookContactFilter" placeholder="Filter by name or contact..."
                            class="w-full pl-12 pr-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[1.5rem] text-[11px] font-bold text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal transition-all shadow-sm">
                        <svg class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <div class="relative group flex-1 w-full">
                        <input type="text" wire:model.live.debounce.300ms="webhookSearch" placeholder="Search message ID or error..."
                            class="w-full pl-12 pr-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[1.5rem] text-[11px] font-bold text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-wa-teal transition-all shadow-sm">
                        <svg class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Stats Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="rounded-3xl border border-slate-100 dark:border-slate-800 p-6 bg-slate-50/50 dark:bg-slate-800/20 shadow-sm transition-all hover:shadow-md group">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-hover:text-wa-teal transition-colors">Total Sent</p>
                <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white leading-none">{{ number_format($webhookSummary['sent']) }}</p>
            </div>
            <div class="rounded-3xl border border-blue-50 dark:border-blue-900/20 p-6 bg-blue-50/30 dark:bg-blue-900/10 shadow-sm transition-all hover:shadow-md group">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 group-hover:text-blue-600 transition-colors">Delivered</p>
                <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white leading-none">{{ number_format($webhookSummary['delivered']) }}</p>
            </div>
            <div class="rounded-3xl border border-emerald-50 dark:border-emerald-900/20 p-6 bg-emerald-50/30 dark:bg-emerald-900/10 shadow-sm transition-all hover:shadow-md group">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-500 group-hover:text-emerald-600 transition-colors">Read</p>
                <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white leading-none">{{ number_format($webhookSummary['read']) }}</p>
            </div>
            <div class="rounded-3xl border border-rose-50 dark:border-rose-900/20 p-6 bg-rose-50/30 dark:bg-rose-900/10 shadow-sm transition-all hover:shadow-md group">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-500 group-hover:text-rose-600 transition-colors">Failed</p>
                <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white leading-none">{{ number_format($webhookSummary['failed']) }}</p>
            </div>
        </div>

        {{-- Log View --}}
        <div class="rounded-[2.5rem] border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
            <div class="max-h-[35rem] overflow-y-auto custom-scrollbar">
            <table class="w-full text-left min-w-[950px] border-separate border-spacing-0">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/40 backdrop-blur-md">
                        <th class="sticky top-0 z-10 px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 dark:border-slate-800">Time</th>
                        <th class="sticky top-0 z-10 px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 dark:border-slate-800">Contact Identity</th>
                        <th class="sticky top-0 z-10 px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 dark:border-slate-800 text-center">Status</th>
                        <th class="sticky top-0 z-10 px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 dark:border-slate-800">Tracking Identity</th>
                        <th class="sticky top-0 z-10 px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 dark:border-slate-800">Processing Logs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                    @forelse($webhookDetails as $message)
                        <tr class="group hover:bg-slate-50/80 dark:hover:bg-indigo-500/[0.02] transition-colors">
                            <td class="px-8 py-5">
                                <div class="text-[11px] font-black text-slate-900 dark:text-white leading-none mb-1">{{ $message->created_at?->format('H:i') }}</div>
                                <div class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">{{ $message->created_at?->format('d M, Y') }}</div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="text-[13px] font-black text-slate-900 dark:text-white leading-none mb-1.5">{{ $message->contact?->name ?: 'Anonymous Client' }}</div>
                                <div class="flex items-center gap-2">
                                     <span class="text-[9px] text-wa-teal font-black uppercase tracking-widest px-1.5 py-0.5 bg-wa-teal/10 rounded">{{ $message->contact?->phone_number ?: 'No Number' }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest shadow-sm
                                {{ $message->status === 'failed' ? 'bg-rose-50 text-rose-500 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-100 dark:border-rose-500/20' : '' }}
                                {{ $message->status === 'read' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-500/20' : '' }}
                                {{ $message->status === 'delivered' ? 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 border border-blue-100 dark:border-blue-500/20' : '' }}
                                {{ $message->status === 'sent' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700/50' : '' }}">
                                   <span class="w-1.5 h-1.5 rounded-full {{ $message->status === 'failed' ? 'bg-rose-500' : ($message->status === 'read' ? 'bg-emerald-500' : ($message->status === 'delivered' ? 'bg-blue-500' : 'bg-slate-400')) }}"></span>
                                    {{ $message->status }}
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-2">
                                    <code class="text-[10px] font-mono text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 px-2 py-1 rounded-lg border border-slate-100 dark:border-slate-800 w-fit block max-w-[180px] truncate" title="{{ $message->whatsapp_message_id }}">
                                        {{ $message->whatsapp_message_id ?: 'NO_ID_ASSIGNED' }}
                                    </code>
                                    @if($message->whatsapp_message_id)
                                        <button onclick="navigator.clipboard.writeText('{{ $message->whatsapp_message_id }}')" class="p-1 text-slate-300 hover:text-wa-teal transition-colors opacity-0 group-hover:opacity-100">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                @if($message->error_message)
                                    <div class="flex items-start gap-2 max-w-[280px]">
                                        <svg class="w-3.5 h-3.5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-[11px] font-medium text-rose-600 dark:text-rose-400 leading-tight italic">{{ $message->error_message }}</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 text-emerald-500/60 dark:text-emerald-500/40">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-[9px] font-black uppercase tracking-widest">Processed Successfully</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center justify-center space-y-4">
                                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                    </div>
                                    <p class="text-sm font-black text-slate-400 uppercase tracking-widest">No activity data available for this range</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            </div>
            <div class="px-8 py-5 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em]">
                    Displaying results <span class="text-slate-900 dark:text-white">{{ $webhookDetails->firstItem() ?? 0 }}-{{ $webhookDetails->lastItem() ?? 0 }}</span> of <span class="text-slate-900 dark:text-white">{{ $webhookDetails->total() }}</span> messages
                </div>
                <div>
                    {{ $webhookDetails->onEachSide(1)->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
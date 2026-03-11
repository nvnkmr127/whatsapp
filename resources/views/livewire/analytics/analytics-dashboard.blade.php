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
            <p class="text-slate-500 font-medium">Monitor your metrics, usage, and financial transactions.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden md:flex flex-col items-end mr-4">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Data Freshness</span>
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
                Export
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
                <p class="mt-4 text-[10px] font-bold text-wa-teal uppercase tracking-widest">Outbound Traffic</p>
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
                <p class="mt-4 text-[10px] font-bold text-purple-500 uppercase tracking-widest">Inbound Engagement</p>
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
                <p class="mt-4 text-[10px] font-bold text-indigo-500 uppercase tracking-widest">QR Conversions</p>
            </div>
        </div>

        <!-- Tickets Resolved -->
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
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Tickets Resolved</h3>
                <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($ticketsResolved) }}
                </div>
                <p class="mt-4 text-[10px] font-bold text-orange-500 uppercase tracking-widest">Customer Support</p>
            </div>
        </div>
    </div>

    <!-- Official Meta Insights (System Style) -->
    @if(!empty($metaAnalytics))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden mb-8">
            <div
                class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                        Official Meta Data
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                        Verified billing and usage metrics directly from WhatsApp Cloud API. Source: Meta Graph API.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                            <circle cx="4" cy="4" r="3" />
                        </svg>
                        Connected
                    </span>
                    <a href="https://business.facebook.com/" target="_blank"
                        class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                        Open Business Manager &rarr;
                    </a>
                </div>
            </div>

            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @php
                        // Display logic
                        $firstDataPoint = isset($metaAnalytics['data'][0]['data_points']) ? $metaAnalytics['data'][0]['data_points'] : [];
                    @endphp

                    <!-- Stat Card 1 -->
                    <div class="bg-gray-50 dark:bg-gray-700 overflow-hidden rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300 truncate">Total Conversation
                                Cost</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                                {{-- Placeholder as actual summation requires parsing --}}
                                @if(!empty($firstDataPoint))
                                    <span class="text-sm text-gray-400">View details in JSON</span>
                                @else
                                    <span class="text-gray-400 text-lg">No Data</span>
                                @endif
                            </dd>
                        </div>
                    </div>

                    <!-- Stat Card 2 -->
                    <div class="bg-gray-50 dark:bg-gray-700 overflow-hidden rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300 truncate">Granularity</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                                Daily
                            </dd>
                        </div>
                    </div>

                    <!-- Stat Card 3 -->
                    <div class="bg-gray-50 dark:bg-gray-700 overflow-hidden rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300 truncate">Data Points</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                                {{ count($metaAnalytics['data'] ?? []) }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- JSON Data Toggle -->
                <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4" x-data="{ open: false }">
                    <button @click="open = !open" type="button"
                        class="group inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                        <svg :class="{'rotate-90': open, 'text-gray-400': !open, 'text-gray-600': open}"
                            class="mr-2 h-5 w-5 transform transition-colors ease-in-out group-hover:text-gray-600 dark:group-hover:text-gray-300"
                            viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>View Raw Analytics Response</span>
                    </button>
                    <div x-show="open"
                        class="mt-4 bg-gray-900 rounded-lg p-4 overflow-x-auto overflow-y-auto max-h-96 custom-scrollbar"
                        style="display: none;">
                        <pre
                            class="text-xs text-green-400 font-mono">{{ json_encode($metaAnalytics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
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
                            <span class="text-wa-teal">Velocity</span>
                        </h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Real-time volume
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

                <div class="relative h-[350px] w-full">
                    <canvas id="messageChart"></canvas>
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
            <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Billing <span
                        class="text-wa-teal">History</span></h3>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Latest Transactions</div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
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
                        @foreach($transactions as $txn)
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
                        @endforeach
                    </tbody>
                </table>
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
                            performance metrics, average resolution time, and satisfaction scores will appear here.</p>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const ctx = document.getElementById('messageChart').getContext('2d');
            const chartData = @json($chartData);
            const isDark = document.documentElement.classList.contains('dark');

            const gradientSent = ctx.createLinearGradient(0, 0, 0, 400);
            gradientSent.addColorStop(0, 'rgba(34, 197, 94, 0.4)');
            gradientSent.addColorStop(1, 'rgba(34, 197, 94, 0)');

            const gradientReceived = ctx.createLinearGradient(0, 0, 0, 400);
            gradientReceived.addColorStop(0, 'rgba(20, 184, 166, 0.4)');
            gradientReceived.addColorStop(1, 'rgba(20, 184, 166, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets.map((ds, index) => ({
                        ...ds,
                        borderColor: index === 0 ? '#22c55e' : '#14b8a6',
                        backgroundColor: index === 0 ? gradientSent : gradientReceived,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointBackgroundColor: index === 0 ? '#22c55e' : '#14b8a6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 3,
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                font: { family: 'Inter', size: 10, weight: '700' },
                                color: '#94a3b8',
                                padding: 10,
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                font: { family: 'Inter', size: 10, weight: '700' },
                                color: '#94a3b8',
                                padding: 10,
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: isDark ? '#1e293b' : '#fff',
                            titleColor: isDark ? '#f8fafc' : '#1e293b',
                            bodyColor: isDark ? '#94a3b8' : '#64748b',
                            titleFont: { size: 13, weight: '900', family: 'Inter' },
                            bodyFont: { size: 12, weight: '700', family: 'Inter' },
                            padding: 15,
                            cornerRadius: 16,
                            displayColors: true,
                            boxWidth: 8,
                            boxHeight: 8,
                            boxPadding: 6,
                            borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)',
                            borderWidth: 1,
                        }
                    }
                }
            });
        });
    </script>
</div>
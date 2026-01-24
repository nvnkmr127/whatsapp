<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Event <span class="text-wa-teal">Stream</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Real-time tracking and intelligence for every customer interaction.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden md:flex flex-col items-end mr-4">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Stream Freshness</span>
                <span class="text-xs font-bold text-wa-teal flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-wa-teal animate-pulse"></span>
                    Recent Event: {{ $lastUpdated->diffForHumans() }}
                </span>
            </div>

            <button wire:click="refreshData" wire:loading.class="animate-spin"
                class="p-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>

            <button wire:click="exportEvents"
                class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 font-bold text-xs uppercase tracking-widest rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Stream
            </button>
        </div>
    </div>

    <!-- Stats Matrix -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl relative overflow-hidden group">
            <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Total Volume</h3>
                <div class="flex items-baseline gap-3">
                    <span class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($totalEvents) }}</span>
                    <span class="text-xs font-bold {{ $growth >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                        {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 1) }}%
                    </span>
                </div>
                <p class="mt-4 text-[10px] font-bold text-wa-teal uppercase tracking-widest">Active Tracking</p>
            </div>
        </div>

        @foreach($eventStats->take(3) as $stat)
            <div class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl relative overflow-hidden group">
                <div class="relative z-10">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">{{ str_replace('_', ' ', $stat->event_type) }}</h3>
                    <div class="text-3xl font-black text-slate-900 dark:text-white">{{ number_format($stat->count) }}</div>
                    <p class="mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Interaction Points</p>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Analytics Cockpit -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Trend Visualization -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 sm:p-10 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                <div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Interaction <span class="text-wa-teal">Velocity</span></h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Daily frequency distribution</p>
                </div>
                <select wire:model.live="filterDateRange" class="px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 focus:ring-2 focus:ring-wa-teal/20">
                    <option value="1">24 Hours</option>
                    <option value="7">7 Days</option>
                    <option value="30">30 Days</option>
                    <option value="90">90 Days</option>
                </select>
            </div>

            <div class="relative h-[250px] w-full">
                <canvas id="eventTrendChart"></canvas>
            </div>
        </div>

        <!-- Event Distribution -->
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 sm:p-10 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden">
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2">Event <span class="text-wa-teal">Mix</span></h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-10">Volume by interaction type</p>
            
            <div class="relative h-[250px] w-full">
                <canvas id="eventDistributionChart"></canvas>
            </div>
        </div>

        <!-- Event Explorer -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4 bg-slate-50/30 dark:bg-slate-800/20">
                <div class="flex items-center gap-6">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Intelligence <span class="text-wa-teal">Log</span></h3>
                    
                    <div class="hidden sm:flex items-center gap-4">
                        <select wire:model.live="filterEventType" class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-slate-400 focus:ring-0 cursor-pointer">
                            <option value="all">Every Event</option>
                            @foreach($eventStats as $stat)
                                <option value="{{ $stat->event_type }}">{{ strtoupper(str_replace('_', ' ', $stat->event_type)) }}</option>
                            @endforeach
                        </select>
                        
                        <div class="h-4 w-px bg-slate-200 dark:bg-slate-800"></div>

                        <select wire:model.live="filterCategory" class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-slate-400 focus:ring-0 cursor-pointer">
                            <option value="all">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ strtoupper($category->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="relative group min-w-[250px]">
                    <input wire:model.live="searchTerm" type="text"
                        class="w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-800 border-none rounded-2xl text-xs font-bold text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-wa-teal/20"
                        placeholder="Search payload...">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800/50">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Time</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Type</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Summary</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                        @forelse($events as $event)
                            <tr wire:key="entry-{{ $event->id }}" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                        {{ $event->occurred_at->format('M d, H:i') }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                                        {{ $event->occurred_at->diffForHumans() }}
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="inline-flex px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest {{ \App\Services\EventPresenter::badgeClass($event) }}">
                                        {{ class_basename($event->event_type) }}
                                    </span>
                                    <div class="text-[9px] text-slate-400 mt-1 uppercase">{{ $event->source }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                        {{ \App\Services\EventPresenter::summary($event) }}
                                    </div>
                                    <div class="font-mono text-[9px] text-slate-400 mt-1">
                                        Trace: {{ Str::limit($event->trace_id ?? '-', 8) }}
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                     <button wire:click="viewEventDetails({{ $event->id }})" class="text-xs font-bold text-wa-teal hover:underline uppercase tracking-wider">
                                        Analyze
                                     </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center text-slate-400 font-black uppercase tracking-widest text-[10px]">No system events found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($events->hasPages())
                <div class="p-8 border-t border-slate-50 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </div>
    
    <!-- Detail Modal Removed - We Redirect to Explorer -->

    <!-- Event Detail Explorer -->
    @if($showDetailModal && $selectedEvent)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity animate-in fade-in duration-300" wire:click="closeDetailModal"></div>
            <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in zoom-in-95 duration-200">
                <div class="p-10 border-b border-slate-50 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Intelligence <span class="text-wa-teal">Deep Dive</span></h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Transaction ID: #{{ $selectedEvent->id }}</p>
                        </div>
                        <button wire:click="closeDetailModal" class="p-3 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-2xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-10 space-y-8">
                    <!-- Context Section -->
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Stakeholder</h4>
                            @if($selectedEvent->contact)
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-lg shadow-sm border border-slate-100 dark:border-slate-800" style="background-color: {{ $selectedEvent->contact->category->color ?? '#25D366' }}15; color: {{ $selectedEvent->contact->category->color ?? '#25D366' }};">
                                        {{ substr($selectedEvent->contact->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $selectedEvent->contact->name ?? 'Unknown' }}</div>
                                        <div class="text-xs font-bold text-wa-teal">{{ $selectedEvent->contact->phone_number }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="text-sm font-bold text-slate-500">Anonymous Interactive User</div>
                            @endif
                        </div>
                        <div>
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Temporal Data</h4>
                            <div class="space-y-1">
                                <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $selectedEvent->created_at->format('F j, Y') }}</div>
                                <div class="text-xs font-bold text-slate-500">{{ $selectedEvent->created_at->format('H:i:s P') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payload Section -->
                    <div>
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Payload Visualization</h4>
                        <div class="bg-slate-900 rounded-[2rem] p-8 border border-slate-800 shadow-inner">
                            <pre class="text-emerald-400 font-mono text-xs overflow-x-auto custom-scrollbar leading-relaxed"><code>{{ json_encode($selectedEvent->event_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        </div>
                    </div>
                </div>

                <div class="px-10 py-8 bg-slate-50/50 dark:bg-slate-800/50 flex justify-end">
                    <button wire:click="closeDetailModal" class="px-10 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl transition-all hover:scale-[1.02] active:scale-95">
                        Acknowledge
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const trendCtx = document.getElementById('eventTrendChart').getContext('2d');
            const distCtx = document.getElementById('eventDistributionChart').getContext('2d');
            let trendChart, distChart;

            function initCharts(chartData = null, distData = null) {
                const isDark = document.documentElement.classList.contains('dark');
                const finalChartData = chartData || @json($chartData);
                const finalDistData = distData || @json($distData);

                // Trend Chart
                const gradient = trendCtx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(37, 211, 102, 0.2)');
                gradient.addColorStop(1, 'rgba(37, 211, 102, 0)');

                if (trendChart) trendChart.destroy();
                trendChart = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: finalChartData.labels,
                        datasets: [{
                            label: 'Interactions',
                            data: finalChartData.datasets[0].data,
                            borderColor: '#25D366',
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#25D366',
                            pointBorderColor: isDark ? '#0f172a' : '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)', drawBorder: false },
                                ticks: { font: { family: 'Inter', size: 10, weight: '700' }, color: '#94a3b8', padding: 10 }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'Inter', size: 10, weight: '700' }, color: '#94a3b8', padding: 10 }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: isDark ? '#1e293b' : '#fff',
                                titleColor: isDark ? '#f8fafc' : '#1e293b',
                                bodyColor: isDark ? '#94a3b8' : '#64748b',
                                titleFont: { size: 12, weight: '900' },
                                bodyFont: { size: 11, weight: '700' },
                                padding: 12,
                                cornerRadius: 12,
                                borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)',
                                borderWidth: 1
                            }
                        }
                    }
                });

                // Distribution Chart
                if (distChart) distChart.destroy();
                distChart = new Chart(distCtx, {
                    type: 'doughnut',
                    data: {
                        labels: finalDistData.labels,
                        datasets: [{
                            data: finalDistData.data,
                            backgroundColor: [
                                '#25D366', '#34B7F1', '#128C7E', '#075E54', '#8ed1fc', '#0693e3', '#abb8c3'
                            ],
                            borderWidth: 0,
                            hoverOffset: 20
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: { family: 'Inter', size: 9, weight: '900' },
                                    color: '#94a3b8',
                                    generateLabels: (chart) => {
                                        const data = chart.data;
                                        return data.labels.map((label, i) => ({
                                            text: label,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            hidden: isNaN(data.datasets[0].data[i]),
                                            index: i
                                        }));
                                    }
                                }
                            }
                        }
                    }
                });
            }

            initCharts();
            
            Livewire.on('refreshCharts', (event) => {
                initCharts(event.params.chartData, event.params.distData);
            });
        });
    </script>
</div>

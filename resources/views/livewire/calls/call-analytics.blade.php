<div class="space-y-10">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-3 bg-wa-blue shadow-[0_0_15px_rgba(52,183,241,0.4)] text-white rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Financial <span class="text-wa-teal">Intelligence</span>
                </h1>
            </div>
            <p class="text-slate-500 font-medium text-lg">Real-time voice traffic cost analysis and usage patterns.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 bg-white dark:bg-slate-900 p-1.5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
            @foreach(['today' => '24H', 'week' => '7D', 'month' => '30D', 'year' => '1Y'] as $value => $label)
                <button wire:click="$set('period', '{{ $value }}')"
                    class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $period === $value ? 'bg-wa-teal text-slate-900 shadow-lg shadow-wa-teal/20' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Limit Engine --}}
    @if(isset($usageLimits['has_limit']) && $usageLimits['has_limit'])
        <div class="group relative bg-slate-900 rounded-[2.5rem] p-10 border border-slate-800 shadow-2xl overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-amber-500/5 to-orange-500/5"></div>
            
            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-10">
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-4 bg-amber-500 text-slate-900 rounded-2xl shadow-[0_0_20px_rgba(245,158,11,0.3)]">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-500/60">Limit Orchestrator</h3>
                            <div class="text-3xl font-black text-white tabular-nums tracking-tighter">
                                {{ number_format($usageLimits['minutes_used'] ?? 0, 0) }} 
                                <span class="text-slate-500 text-xl font-bold">/ {{ number_format($usageLimits['minutes_limit'] ?? 0, 0) }} used</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden">
                        <div class="bg-amber-500 h-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(245,158,11,0.5)]"
                            style="width: {{ min($usageLimits['percent_used'] ?? 0, 100) }}%">
                        </div>
                    </div>
                </div>

                <div class="bg-white/5 border border-white/10 rounded-3xl p-6 md:min-w-[240px] text-center">
                    <div class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1">Total Availability</div>
                    <div class="text-5xl font-black text-white tabular-nums">{{ number_format($usageLimits['minutes_remaining'] ?? 0, 0) }}</div>
                    <div class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-500 mt-2">Minutes Left</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Economic Matrix -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach([
            ['label' => 'Total Calls', 'val' => $statistics['total_calls'] ?? 0, 'desc' => 'This ' . ucfirst($period), 'color' => 'blue'],
            ['label' => 'Success Rate', 'val' => ($statistics['success_rate'] ?? 0) . '%', 'desc' => ($statistics['completed_calls'] ?? 0) . ' completed', 'color' => 'teal'],
            ['label' => 'Total Duration', 'val' => number_format($statistics['total_duration_minutes'] ?? 0, 0), 'desc' => 'Minutes consumed', 'color' => 'purple'],
            ['label' => 'Consolidated Cost', 'val' => get_setting('currency_symbol', '$') . number_format($statistics['total_cost'] ?? 0, 2), 'desc' => 'Billing estimation', 'color' => 'amber']
        ] as $stat)
            @php
                $colors = ['blue' => 'from-wa-blue to-indigo-600', 'purple' => 'from-purple-500 to-fuchsia-600', 'teal' => 'from-wa-teal to-emerald-600', 'amber' => 'from-amber-400 to-orange-600'];
                $color = $colors[$stat['color']];
            @endphp
            <div class="group relative bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden transition-all hover:scale-[1.02]">
                <div class="absolute -top-12 -right-12 w-32 h-32 bg-gradient-to-br {{ $color }} opacity-5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                
                <div class="relative">
                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">{{ $stat['label'] }}</h4>
                    <div class="text-3xl font-black text-slate-900 dark:text-white tabular-nums tracking-tighter">{{ $stat['val'] }}</div>
                    <p class="mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $stat['desc'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Analytics Cockpit -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Revenue Velocity Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden">
             <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 blur-3xl rounded-full -mr-32 -mt-32"></div>
             
             <div class="relative">
                <div class="flex items-center justify-between mb-10">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Economic <span class="text-wa-teal">Velocity</span></h3>
                        <p class="text-slate-500 font-medium">Daily billing trends and cost accumulation.</p>
                    </div>
                </div>

                <div class="relative h-[320px] w-full -ml-4">
                    <canvas id="costVelocityChart"></canvas>
                </div>
             </div>
        </div>

        <!-- Call Quality Trends [NEW] -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden">
             <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 blur-3xl rounded-full -mr-32 -mt-32"></div>
             <div class="relative">
                <div class="flex items-center justify-between mb-10">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Quality & <span class="text-wa-teal">Latency</span> Intensity</h3>
                        <p class="text-slate-500 font-medium">Aggregate MOS score and signal transmission delay metrics.</p>
                    </div>
                </div>
                <div class="relative h-[320px] w-full -ml-4">
                    <canvas id="qualityTrendsChart"></canvas>
                </div>
             </div>
        </div>

        <!-- Outcome Mix -->
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden flex flex-col">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2">Outcome <span class="text-wa-teal">Index</span></h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-10">Historical success distribution</p>
            
            <div class="flex-1 relative min-h-[250px] w-full flex items-center justify-center">
                <canvas id="outcomeIndexChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-2xl font-black text-slate-900 dark:text-white">{{ $statistics['success_rate'] ?? 0 }}%</span>
                    <span class="text-[8px] font-black uppercase text-wa-teal tracking-widest">Efficiency</span>
                </div>
            </div>
        </div>

        <!-- Stakeholder Analysis -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 blur-3xl rounded-full -mr-32 -mt-32"></div>
            
            <div class="px-8 py-8 border-b border-slate-50 dark:border-slate-800/50">
                <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Top <span class="text-wa-teal">Collaborators</span></h3>
                <p class="text-slate-500 font-medium text-sm">Economic analysis by interaction point.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800/50">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Collaborator</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Interaction Volume</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Duration</th>
                            <th class="px-8 py-6 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Economic Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                        @forelse($topContacts as $item)
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all duration-300">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-[1.25rem] bg-slate-100 dark:bg-slate-800 text-wa-teal flex items-center justify-center font-black text-lg shadow-inner">
                                            {{ substr($item['contact']->name ?? 'U', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $item['contact']->name ?? 'Unknown Actor' }}</div>
                                            <div class="text-[10px] text-slate-500 font-bold tabular-nums uppercase tracking-widest mt-1">{{ $item['contact']->phone_number ?? 'No Identifier' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-sm font-black text-slate-900 dark:text-white tabular-nums tracking-tighter">{{ $item['total_calls'] }} Interactions</div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-sm font-black text-slate-900 dark:text-white tabular-nums tracking-tighter">{{ number_format($item['total_duration'] / 60, 1) }} mins</div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="text-xl font-black text-wa-blue tracking-tighter">{{ get_setting('currency_symbol', '$') }}{{ number_format($item['total_cost'], 2) }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center text-slate-400 font-black uppercase tracking-widest text-[10px]">Broadcast Silence: No Economic Data Captured</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @script
    <script>
        {
            const costCtx = document.getElementById('costVelocityChart').getContext('2d');
            const outcomeCtx = document.getElementById('outcomeIndexChart').getContext('2d');
            let costChart, outcomeChart;

            const currencySymbol = "{{ get_setting('currency_symbol', '$') }}";
            const qualityCtx = document.getElementById('qualityTrendsChart').getContext('2d');
            let qualityChart;

            function initCharts() {
                const isDark = document.documentElement.classList.contains('dark');
                const costData = @json($costBreakdown);
                const qualityData = @json($qualityTrends);
                
                // Cost Velocity Chart
                const gradient = costCtx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(52, 183, 241, 0.2)');
                gradient.addColorStop(1, 'rgba(52, 183, 241, 0)');

                if (costChart) costChart.destroy();
                costChart = new Chart(costCtx, {
                    type: 'line',
                    data: {
                        labels: costData.map(d => d.date),
                        datasets: [{
                            label: 'Cost',
                            data: costData.map(d => d.cost),
                            borderColor: '#34B7F1',
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 4,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#34B7F1',
                            pointBorderColor: isDark ? '#0f172a' : '#fff',
                            pointBorderWidth: 3,
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
                                ticks: { font: { family: 'Inter', size: 10, weight: '700' }, color: '#94a3b8', padding: 10, callback: (v) => currencySymbol + v }
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
                                padding: 16,
                                cornerRadius: 16,
                                borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)',
                                borderWidth: 1,
                                displayColors: false,
                                callbacks: {
                                    label: (ctx) => `${currencySymbol}${ctx.raw.toFixed(2)}`
                                }
                            }
                        }
                    }
                });
                
                // Quality Metrics Chart
                if (qualityChart) qualityChart.destroy();
                qualityChart = new Chart(qualityCtx, {
                    type: 'bar',
                    data: {
                        labels: qualityData.map(d => d.date),
                        datasets: [
                            {
                                label: 'Avg MOS Score',
                                data: qualityData.map(d => d.avg_mos),
                                backgroundColor: '#25D366',
                                borderRadius: 8,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Latency (ms)',
                                data: qualityData.map(d => d.avg_latency),
                                borderColor: '#34B7F1',
                                borderDash: [5, 5],
                                borderWidth: 2,
                                type: 'line',
                                pointRadius: 4,
                                yAxisID: 'y1',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { 
                                type: 'linear', position: 'left', min: 0, max: 5,
                                grid: { display: false },
                                ticks: { font: { family: 'Inter', size: 9, weight: '900' }, color: '#94a3b8' }
                            },
                            y1: { 
                                type: 'linear', position: 'right', min: 0,
                                grid: { color: 'rgba(255,255,255,0.05)' },
                                ticks: { font: { family: 'Inter', size: 9, weight: '700' }, color: '#94a3b8' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'Inter', size: 9, weight: '900' }, color: '#94a3b8' }
                            }
                        },
                        plugins: {
                            legend: { display: true, position: 'top', align: 'end', labels: { boxWidth: 10, font: { weight: '900', size: 9 } } }
                        }
                    }
                });

                // Outcome Index Chart
                if (outcomeChart) outcomeChart.destroy();
                outcomeChart = new Chart(outcomeCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed', 'Failed', 'Other'],
                        datasets: [{
                            data: [
                                {{ $billingStats['completed_calls'] ?? 0 }},
                                {{ $billingStats['failed_calls'] ?? 0 }},
                                {{ max(0, ($billingStats['total_calls'] ?? 0) - (($billingStats['completed_calls'] ?? 0) + ($billingStats['failed_calls'] ?? 0))) }}
                            ],
                            backgroundColor: ['#25D366', '#EF4444', '#94A3B8'],
                            borderWidth: 0,
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '80%',
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 25,
                                    font: { family: 'Inter', size: 9, weight: '900' },
                                    color: '#94a3b8',
                                }
                            },
                        }
                    }
                });
            }

            initCharts();
            $wire.on('refreshCharts', () => initCharts());
        }
    </script>
    @endscript
</div>
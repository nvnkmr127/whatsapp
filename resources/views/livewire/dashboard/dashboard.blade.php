<div class="space-y-10" wire:init="load">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                Main <span class="text-orange-500">Dashboard</span>
            </h1>
            <p class="mt-2 text-slate-400 dark:text-zinc-400 font-medium">
                Welcome back, <span
                    class="text-slate-900 dark:text-white font-bold">{{ auth()->user()->name }}</span>. Your
                WhatsApp Business account is active.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <div class="hidden md:flex flex-col items-end mr-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-zinc-500">Last Updated</span>
                <span class="text-xs font-bold text-orange-400 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span>
                    Stats Updated: {{ $lastUpdated->diffForHumans() }}
                </span>
            </div>

            <button wire:click="refreshData" wire:loading.class="animate-spin"
                class="p-2.5 bg-white dark:bg-zinc-900 text-slate-400 dark:text-zinc-400 hover:text-orange-400 rounded-2xl border border-black/[0.06] dark:border-zinc-800 shadow-sm transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>

            <div
                class="flex items-center gap-2 bg-white dark:bg-zinc-900 p-1 rounded-2xl shadow-sm border border-black/[0.06] dark:border-zinc-800">
                @foreach(['today' => 'Today', 'this_week' => 'Week', 'month' => 'Month'] as $key => $label)
                    <button wire:click="updateTimeRange('{{ $key }}')"
                        class="px-5 py-2 text-xs font-black uppercase tracking-widest rounded-xl transition-all duration-300 {{ $timeRange === $key ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/30' : 'text-slate-500 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-zinc-800' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Super Admin Command Center (Only for Super Admins) -->
    @if(auth()->user()->isSuperAdmin())
        <div
            class="bg-indigo-600 dark:bg-indigo-950/40 rounded-[2.5rem] p-8 border border-indigo-400/20 shadow-2xl shadow-indigo-500/20 relative overflow-hidden group mb-6">
            <div
                class="absolute -right-12 -top-12 w-64 h-64 bg-white/10 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-1000">
            </div>

            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-8">
                <div>
                    <h2 class="text-2xl font-black text-white uppercase tracking-tight">System Controls</h2>
                    <p class="text-indigo-100 font-medium mt-1">Manage users, settings, and billing.</p>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('admin.dashboard') }}"
                        class="px-6 py-3 bg-white text-indigo-600 font-black uppercase tracking-widest text-xs rounded-2xl shadow-lg hover:scale-105 active:scale-95 transition-all">
                        Tenant Manager
                    </a>
                    <a href="{{ route('settings.system') }}"
                        class="px-6 py-3 bg-white/10 text-white font-black uppercase tracking-widest text-xs rounded-2xl border border-white/20 backdrop-blur-md hover:bg-white/20 transition-all">
                        Platform Settings
                    </a>
                    <a href="{{ route('admin.email-templates.index') }}"
                        class="px-6 py-3 bg-white/10 text-white font-black uppercase tracking-widest text-xs rounded-2xl border border-white/20 backdrop-blur-md hover:bg-white/20 transition-all">
                        Mail Engine
                    </a>
                    <a href="{{ route('admin.plans') }}"
                        class="px-6 py-3 bg-white/10 text-white font-black uppercase tracking-widest text-xs rounded-2xl border border-white/20 backdrop-blur-md hover:bg-white/20 transition-all">
                        Billing Plans
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Onboarding Checklist -->
    @livewire('onboarding.setup-checklist')

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @if($readyToLoad)
        @foreach ($stats as $stat)
            @php
                $colorClasses = [
                    'blue' => 'from-blue-500 to-indigo-600 shadow-blue-500/10 text-blue-500',
                    'purple' => 'from-purple-500 to-fuchsia-600 shadow-purple-500/10 text-purple-500',
                    'green' => 'from-orange-500 to-amber-500 shadow-orange-500/10 text-orange-500',
                    'orange' => 'from-orange-400 to-rose-500 shadow-orange-500/10 text-orange-500',
                ];
                $colorClass = $colorClasses[$stat['color']] ?? $colorClasses['green'];
            @endphp
            <div
                class="group relative bg-white dark:bg-zinc-900 rounded-[2rem] p-8 shadow-sm border border-black/[0.06] dark:border-zinc-800 transition-all duration-500 hover:scale-[1.02] hover:shadow-xl overflow-hidden">
                <!-- Decorative Circle -->
                <div
                    class="absolute -top-12 -right-12 w-32 h-32 bg-gradient-to-br {{ $colorClass }} opacity-10 rounded-full group-hover:scale-150 transition-transform duration-700">
                </div>

                <div class="relative flex flex-col h-full">
                    <div class="flex items-center justify-between mb-8">
                        <div
                            class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 group-hover:bg-gradient-to-br group-hover:{{ $colorClass }} group-hover:text-white transition-all duration-300 shadow-inner">
                            @if($stat['icon'] === 'message-circle')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            @elseif($stat['icon'] === 'users')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            @elseif($stat['icon'] === 'megaphone')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                            @elseif($stat['icon'] === 'file-text')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            @endif
                        </div>
                        <div class="text-right">
                            <span
                                class="text-[10px] uppercase font-black tracking-widest text-slate-400 group-hover:text-slate-500 transition-colors">
                                Total {{ $stat['header'] }}
                            </span>
                            <div class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">
                                {{ $stat['header_value'] }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <div class="mb-2 text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                            {{ $stat['value'] }}
                        </div>
                        <div
                            class="text-sm font-bold text-slate-400 dark:text-zinc-500 group-hover:text-slate-500 dark:group-hover:text-zinc-400 transition-colors">
                            {{ $stat['title'] }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        @else
            @foreach(range(1,4) as $i)
                <div class="bg-white dark:bg-zinc-900 rounded-[2rem] p-8 animate-pulse border border-black/[0.06] dark:border-zinc-800">
                    <div class="flex justify-between mb-8">
                        <div class="w-14 h-14 bg-slate-100 dark:bg-zinc-800 rounded-2xl"></div>
                        <div class="space-y-2">
                            <div class="w-20 h-2 bg-slate-100 dark:bg-zinc-800 rounded-full"></div>
                            <div class="w-16 h-4 bg-slate-100 dark:bg-zinc-800 rounded-full"></div>
                        </div>
                    </div>
                    <div class="mt-auto space-y-3">
                        <div class="w-24 h-8 bg-slate-100 dark:bg-zinc-800 rounded-lg"></div>
                        <div class="w-32 h-3 bg-slate-100 dark:bg-zinc-800 rounded-full"></div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="{{ route('campaigns.create') }}"
            class="group bg-orange-500 p-6 rounded-[2rem] shadow-xl shadow-orange-500/20 hover:scale-[1.02] hover:bg-orange-400 transition-all">
            <div class="flex flex-col h-full justify-between">
                <div class="p-3 w-fit rounded-2xl bg-white/20 text-white mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-tight">New Broadcast</h3>
                    <p class="text-white/70 text-xs font-bold uppercase tracking-widest mt-1">Start Campaign</p>
                </div>
            </div>
        </a>

        <a href="{{ route('commerce.orders') }}"
            class="group bg-white dark:bg-zinc-900 p-6 rounded-[2rem] shadow-sm border border-black/[0.06] dark:border-zinc-800 hover:border-black/[0.12] dark:hover:border-zinc-700 hover:scale-[1.02] hover:shadow-xl transition-all">
            <div class="flex flex-col h-full justify-between">
                <div class="p-3 w-fit rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Manage Orders</h3>
                    <p class="text-slate-400 dark:text-zinc-500 text-xs font-bold uppercase tracking-widest mt-1">Sales Hub</p>
                </div>
            </div>
        </a>

        <a href="{{ route('knowledge-base.index') }}"
            class="group bg-white dark:bg-zinc-900 p-6 rounded-[2rem] shadow-sm border border-black/[0.06] dark:border-zinc-800 hover:border-black/[0.12] dark:hover:border-zinc-700 hover:scale-[1.02] hover:shadow-xl transition-all">
            <div class="flex flex-col h-full justify-between">
                <div class="p-3 w-fit rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Train AI</h3>
                    <p class="text-slate-400 dark:text-zinc-500 text-xs font-bold uppercase tracking-widest mt-1">Knowledge Base</p>
                </div>
            </div>
        </a>

        <a href="{{ route('teams.whatsapp_config') }}"
            class="group bg-white dark:bg-zinc-900 p-6 rounded-[2rem] shadow-sm border border-black/[0.06] dark:border-zinc-800 hover:border-black/[0.12] dark:hover:border-zinc-700 hover:scale-[1.02] hover:shadow-xl transition-all">
            <div class="flex flex-col h-full justify-between">
                <div class="p-3 w-fit rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Account Hub</h3>
                    <p class="text-slate-400 dark:text-zinc-500 text-xs font-bold uppercase tracking-widest mt-1">Configure Setup</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Message Stats Chart -->
        <div
            class="lg:col-span-3 bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border border-black/[0.06] dark:border-zinc-800 p-8 sm:p-10 relative overflow-hidden">
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-orange-500/5 blur-3xl rounded-full -mr-32 -mt-32"></div>

            <div class="relative">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 mb-10">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                            Message <span class="text-orange-500">Speed</span>
                        </h3>
                        <p class="text-slate-400 dark:text-zinc-500 font-medium">Live message tracking</p>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-black uppercase tracking-widest text-slate-400 dark:text-zinc-500">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-orange-500 shadow-lg shadow-orange-500/30"></span> Inbound
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-400 shadow-lg shadow-amber-400/30"></span> Outbound
                        </div>
                    </div>
                </div>

                <div wire:loading.flex class="h-[400px] w-full items-center justify-center">
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-orange-500/20 border-t-orange-500 rounded-full animate-spin">
                        </div>
                    </div>
                </div>

                <div wire:loading.remove id="message-chart-container" class="w-full h-[400px] -ml-4">
                    <div id="chart" data-chart='@json($chartData)'></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        let chart = null;

        const normalizeChartData = (data) => {
            data = (data && typeof data === 'object') ? data : {};
            const labels = Array.isArray(data.labels) ? data.labels : [];

            let series = Array.isArray(data.series) ? data.series : [];
            series = series
                .filter(s => s && typeof s === 'object')
                .map(s => ({
                    name: typeof s.name === 'string' ? s.name : 'Messages',
                    data: Array.isArray(s.data) ? s.data : [],
                }));

            if (series.length === 0) {
                series = [{ name: 'Messages', data: [] }];
            }

            return { labels, series };
        };

        const initChart = (data) => {
            const normalized = normalizeChartData(data);
            const isDark = document.documentElement.classList.contains('dark');
            const axisLabelColor = isDark ? '#71717a' : '#94a3b8';
            const gridColor = isDark ? 'rgba(63,63,70,0.6)' : 'rgba(0,0,0,0.07)';
            const tooltipBg = isDark ? '#18181b' : '#ffffff';
            const tooltipBorder = isDark ? '#3f3f46' : '#e2e8f0';
            const tooltipLabel = isDark ? '#71717a' : '#94a3b8';
            const tooltipValue = isDark ? '#ffffff' : '#0f172a';
            const options = {
                series: normalized.series,
                chart: {
                    type: 'area',
                    height: 400,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: { show: false },
                    background: 'transparent',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: { enabled: true, delay: 150 }
                    },
                    sparkline: { enabled: false }
                },
                colors: ['#f97316', '#fbbf24'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.3,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                markers: {
                    size: 0,
                    colors: ['#fff'],
                    strokeColors: ['#f97316', '#fbbf24'],
                    strokeWidth: 3,
                    hover: { size: 6 }
                },
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: 4,
                    lineCap: 'round'
                },
                xaxis: {
                    categories: normalized.labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: { colors: axisLabelColor, fontSize: '11px', fontWeight: 600 }
                    }
                },
                yaxis: {
                    labels: {
                        style: { colors: axisLabelColor, fontSize: '11px', fontWeight: 600 },
                        formatter: (value) => Math.floor(value)
                    }
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 8,
                    padding: { left: 0, right: 0 }
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                        return '<div style="padding:12px 16px;background:' + tooltipBg + ';border:1px solid ' + tooltipBorder + ';border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.15)">' +
                            '<div style="font-size:10px;text-transform:uppercase;font-weight:900;color:' + tooltipLabel + ';margin-bottom:4px;letter-spacing:0.1em">' + w.globals.categoryLabels[dataPointIndex] + '</div>' +
                            '<div style="display:flex;align-items:center;gap:8px">' +
                            '<span style="width:8px;height:8px;border-radius:50%;background:' + w.globals.colors[seriesIndex] + ';display:inline-block"></span>' +
                            '<span style="font-size:14px;font-weight:900;color:' + tooltipValue + '">' + series[seriesIndex][dataPointIndex] + ' Messages</span>' +
                            '</div>' +
                            '</div>';
                    }
                }
            };

            const chartEl = document.querySelector("#chart");
            if (chartEl) {
                if (chart) {
                    chart.destroy();
                }
                chart = new ApexCharts(chartEl, options);
                chart.render();
            }
        };

        const initialChartEl = document.querySelector("#chart");
        let initialChartData = {};
        try {
            initialChartData = initialChartEl?.dataset?.chart ? JSON.parse(initialChartEl.dataset.chart) : {};
        } catch (e) {
            initialChartData = {};
        }
        initChart(initialChartData);

        Livewire.on('chartDataUpdated', (data) => {
            if (Array.isArray(data)) data = data[0];
            initChart(data);
        });
    });
</script>

<div class="space-y-10">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                Console <span class="text-wa-green">Overview</span>
            </h1>
            <p class="mt-2 text-slate-500 font-medium">
                Welcome back, <span
                    class="text-slate-900 dark:text-slate-200 font-bold">{{ auth()->user()->name }}</span>. Your
                WhatsApp Business account is active.
            </p>
        </div>
        <div
            class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
            @foreach(['today' => 'Today', 'this_week' => 'Week', 'month' => 'Month'] as $key => $label)
                <button wire:click="updateTimeRange('{{ $key }}')"
                    class="px-5 py-2 text-xs font-black uppercase tracking-widest rounded-xl transition-all duration-300 {{ $timeRange === $key ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $stat)
            @php
                $colorClasses = [
                    'blue' => 'from-blue-500 to-indigo-600 shadow-blue-500/10 text-blue-500',
                    'purple' => 'from-purple-500 to-fuchsia-600 shadow-purple-500/10 text-purple-500',
                    'green' => 'from-wa-green to-wa-teal shadow-green-500/10 text-wa-green',
                    'orange' => 'from-orange-400 to-rose-500 shadow-orange-500/10 text-orange-500',
                ];
                $colorClass = $colorClasses[$stat['color']] ?? $colorClasses['green'];
            @endphp
            <div
                class="group relative bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 transition-all duration-500 hover:scale-[1.02] hover:shadow-2xl overflow-hidden">
                <!-- Decorative Circle -->
                <div
                    class="absolute -top-12 -right-12 w-32 h-32 bg-gradient-to-br {{ $colorClass }} opacity-10 rounded-full group-hover:scale-150 transition-transform duration-700">
                </div>

                <div class="relative flex flex-col h-full">
                    <div class="flex items-center justify-between mb-8">
                        <div
                            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 group-hover:bg-gradient-to-br group-hover:{{ $colorClass }} group-hover:text-white transition-all duration-300 shadow-inner">
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
                            class="text-sm font-bold text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-400 transition-colors">
                            {{ $stat['title'] }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Message Stats Chart -->
        <div
            class="lg:col-span-3 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8 sm:p-10 relative overflow-hidden">
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-wa-green/5 blur-3xl rounded-full -mr-32 -mt-32"></div>

            <div class="relative">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 mb-10">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                            Message <span class="text-wa-teal">Velocity</span>
                        </h3>
                        <p class="text-slate-500 font-medium">Real-time volume tracking across all channels</p>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-black uppercase tracking-widest text-slate-400">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-wa-green shadow-lg shadow-wa-green/20"></span> Inbound
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-wa-blue shadow-lg shadow-wa-blue/20"></span> Outbound
                        </div>
                    </div>
                </div>

                <div wire:loading.flex class="h-[400px] w-full items-center justify-center">
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-wa-green/20 border-t-wa-green rounded-full animate-spin">
                        </div>
                    </div>
                </div>

                <div wire:loading.remove id="message-chart-container" class="w-full h-[400px] -ml-4">
                    <div id="chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        let chart = null;

        const initChart = (data) => {
            const options = {
                series: data.series,
                chart: {
                    type: 'area',
                    height: 400,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: { enabled: true, delay: 150 }
                    },
                    sparkline: { enabled: false }
                },
                colors: ['#25D366', '#34B7F1'],
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
                    strokeColors: ['#25D366', '#34B7F1'],
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
                    categories: data.labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: { colors: '#94a3b8', fontSize: '11px', fontWeight: 600 }
                    }
                },
                yaxis: {
                    labels: {
                        style: { colors: '#94a3b8', fontSize: '11px', fontWeight: 600 },
                        formatter: (value) => Math.floor(value)
                    }
                },
                grid: {
                    borderColor: 'rgba(148, 163, 184, 0.1)',
                    strokeDashArray: 8,
                    padding: { left: 0, right: 0 }
                },
                tooltip: {
                    theme: document.documentElement.className.includes('dark') ? 'dark' : 'light',
                    custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                        return '<div class="px-4 py-3 bg-white dark:bg-slate-800 shadow-2xl border-none rounded-xl">' +
                            '<div class="text-[10px] uppercase font-black text-slate-400 mb-1 tracking-widest">' + w.globals.categoryLabels[dataPointIndex] + '</div>' +
                            '<div class="flex items-center gap-3">' +
                            '<span class="w-2 h-2 rounded-full" style="background-color:' + w.globals.colors[seriesIndex] + '"></span>' +
                            '<span class="text-sm font-black text-slate-800 dark:text-slate-100">' + series[seriesIndex][dataPointIndex] + ' Messages</span>' +
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

        initChart(@json($chartData));

        Livewire.on('chartDataUpdated', (data) => {
            if (Array.isArray(data)) data = data[0];
            initChart(data);
        });
    });
</script>
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-green/10 text-wa-green rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Performance
                    <span class="text-wa-green">Vault</span></h1>
            </div>
            <p class="text-slate-500 font-medium">Detailed metrics of your enterprise communication efficiency.</p>
        </div>
        <div
            class="px-4 py-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-50 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-black uppercase tracking-widest text-slate-400">Insight Window:</span>
            <span class="ml-2 text-xs font-black text-wa-teal uppercase">Last 30 Days</span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Messages Sent -->
        <div
            class="group bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 transition-all hover:scale-[1.02] relative overflow-hidden">
            <div
                class="absolute -right-4 -top-4 w-32 h-32 bg-wa-green/5 rounded-full blur-3xl group-hover:bg-wa-green/10 transition-colors">
            </div>
            <div class="flex items-start justify-between mb-4">
                <div class="p-4 bg-wa-green/10 text-wa-green rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
            </div>
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Outbound Logistics</h3>
            <div class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter">
                {{ number_format($stats['sent'] ?? 0) }}</div>
            <div class="text-[10px] font-bold text-wa-green mt-2 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                        d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                Messages Dispatched
            </div>
        </div>

        <!-- Messages Received -->
        <div
            class="group bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 transition-all hover:scale-[1.02] relative overflow-hidden">
            <div
                class="absolute -right-4 -top-4 w-32 h-32 bg-wa-teal/5 rounded-full blur-3xl group-hover:bg-wa-teal/10 transition-colors">
            </div>
            <div class="flex items-start justify-between mb-4">
                <div class="p-4 bg-wa-teal/10 text-wa-teal rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Inbound Traffic</h3>
            <div class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter">
                {{ number_format($stats['received'] ?? 0) }}</div>
            <div class="text-[10px] font-bold text-wa-teal mt-2 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                        d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                Responses Captured
            </div>
        </div>

        <!-- Conversations -->
        <div
            class="group bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 transition-all hover:scale-[1.02] relative overflow-hidden">
            <div
                class="absolute -right-4 -top-4 w-32 h-32 bg-wa-blue/5 rounded-full blur-3xl group-hover:bg-wa-blue/10 transition-colors">
            </div>
            <div class="flex items-start justify-between mb-4">
                <div class="p-4 bg-wa-blue/10 text-wa-blue rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                    </svg>
                </div>
            </div>
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Session Volume</h3>
            <div class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter">
                {{ number_format($stats['conversations'] ?? 0) }}</div>
            <div class="text-[10px] font-bold text-wa-blue mt-2 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Active Engagements
            </div>
        </div>
    </div>

    <!-- Chart Card -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[3rem] p-10 shadow-2xl border border-slate-50 dark:border-slate-800/50">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Traffic
                    Distribution</h3>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Holistic view of message
                    exchange patterns</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-wa-green/5 rounded-lg border border-wa-green/10">
                    <span class="w-2 h-2 rounded-full bg-wa-green"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-wa-green">Sent</span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 bg-wa-teal/5 rounded-lg border border-wa-teal/10">
                    <span class="w-2 h-2 rounded-full bg-wa-teal"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-wa-teal">Received</span>
                </div>
            </div>
        </div>

        <div class="relative h-[450px] w-full">
            <canvas id="messageChart"></canvas>
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
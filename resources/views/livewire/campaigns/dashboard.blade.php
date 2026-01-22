@php
    $campaign = $this->campaign;
    $metrics = $this->metrics;
    $percent = $metrics['total'] > 0 ? round(($metrics['sent'] / $metrics['total']) * 100) : 0;

    // Status Styles
    $statusConfig = [
        'completed' => ['color' => 'wa-teal', 'label' => 'Success'],
        'failed' => ['color' => 'rose-500', 'label' => 'Failed'],
        'processing' => ['color' => 'wa-blue', 'label' => 'Ongoing'],
        'queued' => ['color' => 'wa-orange', 'label' => 'Pending'],
        'scheduled' => ['color' => 'slate-400', 'label' => 'Scheduled'],
    ];
    $status = $statusConfig[$campaign->status] ?? ['color' => 'slate-400', 'label' => $campaign->status];
@endphp

<div class="space-y-8 pb-20">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Live <span class="text-wa-teal">Control Center</span>
                </h1>
            </div>
            <div class="flex items-center gap-2">
                <p class="text-slate-500 font-medium">Monitoring: <span
                        class="text-slate-900 dark:text-slate-200 font-bold">{{ $campaign->campaign_name }}</span></p>
                <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                <span
                    class="px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-md bg-{{ $status['color'] }}/10 text-{{ $status['color'] }} border border-{{ $status['color'] }}/20">
                    {{ $status['label'] }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('campaigns.show', $campaign->id) }}"
                class="px-6 py-3 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-slate-900 dark:text-white text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm">
                Full Report
            </a>
            <a href="{{ route('campaigns.index') }}"
                class="px-6 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 transition-all active:scale-95">
                Campaign List
            </a>
        </div>
    </div>

    <!-- Live Performance Funnel -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-2xl shadow-slate-200/50 dark:shadow-none border border-slate-50 dark:border-slate-800 relative overflow-hidden">
        <!-- Background Accents -->
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-wa-teal/5 rounded-full blur-3xl"></div>
        <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-wa-blue/5 rounded-full blur-3xl"></div>

        <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <!-- Progress Dial -->
            <div class="lg:col-span-4 flex flex-col items-center justify-center text-center">
                <div class="relative w-48 h-48 mb-6">
                    <!-- Progress Ring -->
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="12" fill="transparent"
                            class="text-slate-100 dark:text-slate-800" />
                        <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="12" fill="transparent"
                            stroke-dasharray="553" stroke-dashoffset="{{ 553 - (553 * $percent / 100) }}"
                            class="text-wa-teal transition-all duration-1000 ease-out" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span
                            class="text-5xl font-black text-slate-900 dark:text-white tabular-nums">{{ $percent }}%</span>
                        <span
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Completion</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800/50 px-4 py-2 rounded-xl">
                    <div class="w-2 h-2 rounded-full bg-wa-teal animate-pulse"></div>
                    <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Live Sync Enabled</span>
                </div>
            </div>

            <!-- Stats Breakdown -->
            <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-8">
                <!-- Sent -->
                <div
                    class="group p-6 rounded-3xl bg-slate-50 dark:bg-slate-800/30 border border-transparent hover:border-wa-teal/20 transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 bg-wa-teal/10 text-wa-teal rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <span class="text-sm font-black text-slate-400 uppercase tracking-widest">Sent</span>
                    </div>
                    <div class="text-3xl font-black text-slate-900 dark:text-white mb-2 tabular-nums">
                        {{ number_format($metrics['sent']) }}</div>
                    <div class="h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-wa-teal transition-all duration-1000"
                            style="width: {{ $metrics['total'] > 0 ? ($metrics['sent'] / $metrics['total']) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>

                <!-- Delivered -->
                <div
                    class="group p-6 rounded-3xl bg-slate-50 dark:bg-slate-800/30 border border-transparent hover:border-wa-blue/20 transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 bg-wa-blue/10 text-wa-blue rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-sm font-black text-slate-400 uppercase tracking-widest">Delivered</span>
                    </div>
                    <div class="text-3xl font-black text-slate-900 dark:text-white mb-2 tabular-nums">
                        {{ number_format($metrics['delivered']) }}</div>
                    <div class="h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-wa-blue transition-all duration-1000"
                            style="width: {{ $metrics['sent'] > 0 ? ($metrics['delivered'] / $metrics['sent']) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>

                <!-- Read -->
                <div
                    class="group p-6 rounded-3xl bg-slate-50 dark:bg-slate-800/30 border border-transparent hover:border-amber-400/20 transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            class="w-10 h-10 bg-amber-400/10 text-amber-500 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <span class="text-sm font-black text-slate-400 uppercase tracking-widest">Read</span>
                    </div>
                    <div class="text-3xl font-black text-slate-900 dark:text-white mb-2 tabular-nums">
                        {{ number_format($metrics['read']) }}</div>
                    <div class="h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-400 transition-all duration-1000"
                            style="width: {{ $metrics['delivered'] > 0 ? ($metrics['read'] / $metrics['delivered']) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>

                <!-- Failed -->
                <div
                    class="group p-6 rounded-3xl bg-slate-50 dark:bg-slate-800/30 border border-transparent hover:border-rose-500/20 transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 bg-rose-500/10 text-rose-500 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-sm font-black text-slate-400 uppercase tracking-widest">Failed</span>
                    </div>
                    <div class="text-3xl font-black text-slate-900 dark:text-white mb-2 tabular-nums">
                        {{ number_format($metrics['failed']) }}</div>
                    <div class="h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full bg-rose-500 transition-all duration-1000"
                            style="width: {{ $metrics['total'] > 0 ? ($metrics['failed'] / $metrics['total']) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Monitor -->
    @if($metrics['failed'] > 0)
        <div
            class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-xl border border-rose-100 dark:border-rose-900/30 overflow-hidden relative">
            <div class="absolute top-0 right-0 p-8 text-rose-500/10">
                <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <div class="relative">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 bg-rose-500 text-white rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Recent Failures
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($campaign->messages()->where('status', 'failed')->latest()->take(6)->get() as $msg)
                        <div
                            class="flex items-center justify-between p-4 bg-rose-50/50 dark:bg-rose-500/5 rounded-2xl border border-rose-100 dark:border-rose-500/10 transition-all hover:bg-rose-50 dark:hover:bg-rose-500/10">
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-bold text-slate-900 dark:text-white">{{ $msg->contact->phone_number }}</span>
                                <span
                                    class="text-[10px] font-black text-rose-400 uppercase tracking-widest mt-0.5">{{ $msg->error_message ?? 'API Rejection' }}</span>
                            </div>
                            <div class="text-[10px] font-bold text-slate-400">
                                {{ $msg->updated_at->diffForHumans() }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
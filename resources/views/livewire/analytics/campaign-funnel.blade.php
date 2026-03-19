<div
    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 sm:p-10 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 blur-3xl rounded-full -mr-32 -mt-32"></div>

    <div class="relative">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-12 gap-4">
            <div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
                    <span class="p-2 bg-wa-teal/10 rounded-xl">
                        <svg class="w-6 h-6 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </span>
                    Campaign <span class="text-wa-teal">Funnel</span>
                </h3>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-2 px-1">Customer journey conversion tracking</p>
            </div>
            
            <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-800/50 p-2 rounded-2xl border border-slate-100 dark:border-slate-800">
                <div class="px-4 text-right">
                    <div class="text-[8px] font-black uppercase text-slate-400 tracking-widest leading-none">Status</div>
                    <div class="text-[10px] font-bold text-wa-teal flex items-center gap-1.5 justify-end">
                        <span class="w-1.5 h-1.5 rounded-full bg-wa-teal animate-pulse"></span>
                        Live
                    </div>
                </div>
                <button wire:click="loadFunnelData" wire:loading.class="animate-spin"
                    class="p-3 bg-white dark:bg-slate-900 text-slate-400 hover:text-wa-teal rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="max-w-4xl mx-auto py-4">
            @php
                $maxVal = collect($funnelData['stages'])->max('value') ?: 1;
            @endphp

            <div class="space-y-4">
                @foreach($funnelData['stages'] as $index => $stage)
                    @php
                        $widthPercent = ($stage['value'] / $maxVal) * 100;
                        $widthPercent = max($widthPercent, 20); // Baseline for visual consistency
                        
                        // Pick colors based on WA tokens
                        $brandColor = $stage['label'] === 'Read' || $stage['label'] === 'Orders' ? '#25D366' : ($stage['label'] === 'Sent' ? '#34B7F1' : '#128C7E');
                        $bgColor = $stage['label'] === 'Read' || $stage['label'] === 'Orders' ? 'rgba(37, 211, 102, 0.1)' : ($stage['label'] === 'Sent' ? 'rgba(52, 183, 241, 0.1)' : 'rgba(18, 140, 126, 0.1)');
                    @endphp

                    <div class="relative group">
                        <!-- Connecting Indicator -->
                        @if($index < count($funnelData['stages']) - 1)
                            <div class="absolute left-[2.4rem] top-full h-4 w-0.5 bg-gradient-to-b from-slate-200 to-transparent dark:from-slate-700 z-0"></div>
                        @endif

                        <div class="relative z-10 flex items-center gap-6">
                            <!-- Stage Number & Icon -->
                            <div class="flex-shrink-0 w-12 h-12 rounded-2xl bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-700 shadow-sm flex items-center justify-center transition-transform group-hover:scale-110 duration-300">
                                <span class="text-wa-teal">
                                    @if($stage['icon'] === 'paper-airplane')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                    @elseif($stage['icon'] === 'check')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    @elseif($stage['icon'] === 'eye')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    @elseif($stage['icon'] === 'chat-bubble-left')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                    @elseif($stage['icon'] === 'bolt')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    @elseif($stage['icon'] === 'user-group')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    @elseif($stage['icon'] === 'shopping-cart')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    @endif
                                </span>
                            </div>

                            <!-- Main Stage Content -->
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-black uppercase tracking-widest text-slate-700 dark:text-slate-200">{{ $stage['label'] }}</span>
                                        @if($index > 0)
                                            @php
                                                $prevVal = $funnelData['stages'][$index - 1]['value'];
                                                $conversion = $prevVal > 0 ? round(($stage['value'] / $prevVal) * 100, 1) : 0;
                                            @endphp
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 text-[10px] font-black tracking-tight">
                                                {{ $conversion }}% Conv.
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-slate-900 dark:text-white">{{ number_format($stage['value']) }}</span>
                                    </div>
                                </div>
                                
                                <!-- Visual Bar Container -->
                                <div class="relative h-6 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700/50">
                                    <!-- Dynamic Progress Bar -->
                                    <div class="absolute inset-y-0 left-0 transition-all duration-1000 ease-out flex items-center justify-end px-3 group-hover:brightness-110"
                                         style="width: {{ $widthPercent }}%; background: {{ $brandColor }};">
                                        <div class="w-1.5 h-1.5 rounded-full bg-white/40 animate-pulse"></div>
                                    </div>
                                    
                                    <!-- Dropoff Overlay -->
                                    @if($index < count($funnelData['stages']) - 1)
                                        @php
                                            $nextVal = $funnelData['stages'][$index + 1]['value'];
                                            $dropoff = $stage['value'] > 0 ? round((($stage['value'] - $nextVal) / $stage['value']) * 100, 1) : 0;
                                        @endphp
                                        <div class="absolute inset-y-0 right-0 px-4 flex items-center pointer-events-none">
                                            <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                                {{ $dropoff }}% Dropoff &rarr;
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Summary Stats Section -->
        <div class="mt-12 pt-8 border-t border-slate-50 dark:border-slate-800">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Outreach Metric -->
                <div class="relative p-6 rounded-3xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/30 overflow-hidden group">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 opacity-5 group-hover:opacity-10 transition-opacity">
                        <svg class="w-24 h-24 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[9px] font-black uppercase tracking-widest text-blue-500 mb-1">Outreach Power</p>
                        @php
                            $readCount = (collect($funnelData['stages'])->firstWhere('label', 'Read')['value'] ?? 0);
                            $sentCount = (collect($funnelData['stages'])->firstWhere('label', 'Sent')['value'] ?? 0);
                            $readRate = $sentCount > 0 ? round(($readCount / $sentCount) * 100, 1) : 0;
                        @endphp
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $readRate }}% <span class="text-xs font-bold text-slate-400">Read</span></div>
                    </div>
                </div>

                <!-- Engagement Metric -->
                <div class="relative p-6 rounded-3xl bg-purple-50/50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-900/30 overflow-hidden group">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 opacity-5 group-hover:opacity-10 transition-opacity">
                        <svg class="w-24 h-24 text-purple-500" fill="currentColor" viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[9px] font-black uppercase tracking-widest text-purple-500 mb-1">Engagement Heat</p>
                        @php
                            $repliesCount = (collect($funnelData['stages'])->firstWhere('label', 'Replied')['value'] ?? 0);
                            $replyRate = $sentCount > 0 ? round(($repliesCount / $sentCount) * 100, 1) : 0;
                        @endphp
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $replyRate }}% <span class="text-xs font-bold text-slate-400">Reply</span></div>
                    </div>
                </div>

                <!-- Success Metric -->
                <div class="relative p-6 rounded-3xl bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-900/30 overflow-hidden group">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 opacity-5 group-hover:opacity-10 transition-opacity">
                        <svg class="w-24 h-24 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[9px] font-black uppercase tracking-widest text-emerald-500 mb-1">Bottom Line</p>
                        @php
                            $ordersCount = (collect($funnelData['stages'])->firstWhere('label', 'Orders')['value'] ?? 0);
                            $overallConv = $sentCount > 0 ? round(($ordersCount / $sentCount) * 100, 2) : 0;
                        @endphp
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $overallConv }}% <span class="text-xs font-bold text-slate-400">ROAS</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
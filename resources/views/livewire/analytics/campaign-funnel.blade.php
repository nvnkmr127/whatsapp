<div
    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 sm:p-10 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 blur-3xl rounded-full -mr-32 -mt-32"></div>

    <div class="relative">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Campaign <span
                        class="text-wa-teal">Funnel</span></h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Conversion velocity from
                    broadcast to order</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden md:block">
                    <div class="text-[8px] font-black uppercase text-slate-400 tracking-widest leading-none">Status
                        Freshness</div>
                    <div class="text-[10px] font-bold text-wa-teal">Updated: {{ $lastRefresh }}</div>
                </div>
                <button wire:click="loadFunnelData" wire:loading.class="animate-spin"
                    class="p-2 text-slate-400 hover:text-wa-teal transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="space-y-4">
            @php
                $maxVal = collect($funnelData['stages'])->max('value') ?: 1;
            @endphp

            @foreach($funnelData['stages'] as $index => $stage)
                <div class="group relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-3">
                            <span class="p-2 bg-{{ $stage['color'] }}/10 text-{{ $stage['color'] }} rounded-lg">
                                <!-- Dynamic Icon -->
                                @if($stage['icon'] === 'paper-airplane')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                @elseif($stage['icon'] === 'check')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                @elseif($stage['icon'] === 'eye')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                @elseif($stage['icon'] === 'chat-bubble-left')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                    </svg>
                                @elseif($stage['icon'] === 'bolt')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                @elseif($stage['icon'] === 'user-group')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                @elseif($stage['icon'] === 'shopping-cart')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                @endif
                            </span>
                            <span
                                class="text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-400">{{ $stage['label'] }}</span>
                        </div>
                        <div class="text-right">
                            <span
                                class="text-sm font-black text-slate-900 dark:text-white">{{ number_format($stage['value']) }}</span>
                        </div>
                    </div>

                    <div class="relative h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="absolute inset-y-0 left-0 bg-{{ $stage['color'] }} transition-all duration-1000 ease-out rounded-full"
                            style="width: {{ ($stage['value'] / $maxVal) * 100 }}%">
                            <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                        </div>
                    </div>

                    @if($index < count($funnelData['stages']) - 1)
                        <div class="flex justify-center -my-1">
                            @php
                                $nextVal = $funnelData['stages'][$index + 1]['value'];
                                $conversion = $stage['value'] > 0 ? round(($nextVal / $stage['value']) * 100, 1) : 0;
                            @endphp
                            <div
                                class="px-2 py-0.5 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-800/50 rounded-md text-[8px] font-black text-slate-400 uppercase tracking-tighter z-10">
                                {{ $conversion }}% CR
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-8 pt-6 border-t border-slate-50 dark:border-slate-800">
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-wa-teal/5 rounded-2xl border border-wa-teal/10">
                    <div class="text-[9px] font-black uppercase tracking-widest text-wa-teal mb-1">Overall Conversion
                    </div>
                    @php
                        $ordersCount = collect($funnelData['stages'])->firstWhere('label', 'Orders')['value'];
                        $sentCount = collect($funnelData['stages'])->firstWhere('label', 'Sent')['value'];
                        $overallConv = $sentCount > 0 ? round(($ordersCount / $sentCount) * 100, 2) : 0;
                    @endphp
                    <div class="text-xl font-black text-slate-900 dark:text-white">{{ $overallConv }}%</div>
                </div>
                <div class="p-4 bg-purple-500/5 rounded-2xl border border-purple-500/10">
                    <div class="text-[9px] font-black uppercase tracking-widest text-purple-500 mb-1">Reply Rate</div>
                    @php
                        $repliesCount = collect($funnelData['stages'])->firstWhere('label', 'Replied')['value'];
                        $replyRate = $sentCount > 0 ? round(($repliesCount / $sentCount) * 100, 1) : 0;
                    @endphp
                    <div class="text-xl font-black text-slate-900 dark:text-white">{{ $replyRate }}%</div>
                </div>
            </div>
        </div>
    </div>
</div>
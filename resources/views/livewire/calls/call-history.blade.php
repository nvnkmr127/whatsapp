<div class="space-y-10">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-3 bg-wa-teal shadow-[0_0_15px_rgba(37,211,102,0.4)] text-slate-900 rounded-2xl">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Call <span class="text-wa-teal">Center</span>
                </h1>
            </div>
            <p class="text-slate-500 font-medium">Enterprise voice orchestration & real-time communication history.</p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <a href="{{ route('calls.analytics') }}"
                class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-900 text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:bg-wa-teal hover:text-slate-900 transition-all rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                View Analytics
            </a>
        </div>
    </div>

    <!-- Usage Snapshot -->
    @if(isset($usageLimits['minutes_limit']) && $usageLimits['minutes_limit'])
        <div class="group relative bg-slate-900 rounded-[2.5rem] p-10 border border-slate-800 shadow-2xl overflow-hidden transition-all hover:scale-[1.01]">
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-wa-teal opacity-5 rounded-full group-hover:scale-110 transition-transform duration-700"></div>
            
            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-10">
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-4 bg-wa-teal text-slate-900 rounded-2xl shadow-[0_0_20px_rgba(37,211,102,0.3)]">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-wa-teal/60">Voice Quota Engine</h3>
                            <div class="text-3xl font-black text-white tabular-nums tracking-tighter">
                                {{ number_format($usageLimits['minutes_used'], 0) }} 
                                <span class="text-slate-500 text-xl font-bold">/ {{ number_format($usageLimits['minutes_limit'], 0) }} min</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden">
                        <div class="bg-wa-teal h-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(37,211,102,0.5)]"
                            style="width: {{ min((($usageLimits['minutes_used'] ?? 0) / ($usageLimits['minutes_limit'] ?? 1)) * 100, 100) }}%">
                        </div>
                    </div>
                </div>

                <div class="bg-white/5 border border-white/10 rounded-3xl p-6 md:min-w-[240px] text-center">
                    <div class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1">Total Availability</div>
                    <div class="text-5xl font-black text-white tabular-nums">{{ number_format($usageLimits['minutes_remaining'], 0) }}</div>
                    <div class="text-[10px] font-black uppercase tracking-[0.2em] text-wa-teal mt-2">Minutes Remaining</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Safeguard Status & Thresholds [NEW] -->
    @if(isset($safeguardStatus))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-xl overflow-hidden relative group">
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-wa-blue opacity-5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Minutely Rate</h4>
                        <span class="text-[10px] font-black {{ $safeguardStatus['rate_min']['percentage'] > 80 ? 'text-rose-500' : 'text-wa-blue' }} tabular-nums">
                            {{ $safeguardStatus['rate_min']['used'] }}/{{ $safeguardStatus['rate_min']['limit'] }}
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full {{ $safeguardStatus['rate_min']['percentage'] > 80 ? 'bg-rose-500' : 'bg-wa-blue' }} transition-all duration-500" style="width: {{ $safeguardStatus['rate_min']['percentage'] }}%"></div>
                    </div>
                    <p class="mt-3 text-[9px] font-medium text-slate-500 uppercase tracking-widest">Calls within current minute window.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-xl overflow-hidden relative group">
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-purple-500 opacity-5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Hourly Throughput</h4>
                        <span class="text-[10px] font-black {{ $safeguardStatus['rate_hour']['percentage'] > 80 ? 'text-rose-500' : 'text-purple-500' }} tabular-nums">
                            {{ $safeguardStatus['rate_hour']['used'] }}/{{ $safeguardStatus['rate_hour']['limit'] }}
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full {{ $safeguardStatus['rate_hour']['percentage'] > 80 ? 'bg-rose-500' : 'bg-purple-500' }} transition-all duration-500" style="width: {{ $safeguardStatus['rate_hour']['percentage'] }}%"></div>
                    </div>
                    <p class="mt-3 text-[9px] font-medium text-slate-500 uppercase tracking-widest">Cumulative hourly orchestration.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-{{ $safeguardStatus['is_suspended'] ? 'rose-500/20' : 'slate-100 dark:border-slate-800' }} shadow-xl overflow-hidden relative group">
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-rose-500 opacity-5 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Survival Threshold</h4>
                        <span class="text-[10px] font-black text-rose-500 tabular-nums">
                            {{ $safeguardStatus['missed_calls']['count'] }}/{{ $safeguardStatus['missed_calls']['threshold'] }}
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full bg-rose-500 transition-all duration-500" style="width: {{ $safeguardStatus['missed_calls']['percentage'] }}%"></div>
                    </div>
                    <p class="mt-3 text-[9px] font-medium {{ $safeguardStatus['is_suspended'] ? 'text-rose-500' : 'text-slate-500' }} uppercase tracking-widest font-black">
                        {{ $safeguardStatus['is_suspended'] ? 'SUSPENDED UNTIL ' . ($safeguardStatus['suspended_until'] ? $safeguardStatus['suspended_until']->format('H:i') : 'N/A') : 'Missed calls in last ' . $safeguardStatus['missed_calls']['window_minutes'] . 'm.' }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Efficiency Stats -->
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
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $stat['desc'] }}</span>
                        <div class="h-1.5 w-16 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r {{ $color }} w-1/2"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Communication Log -->
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden relative">
        <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 blur-3xl rounded-full -mr-32 -mt-32"></div>

        <!-- Sleek Filter Console -->
        <div class="p-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-col xl:flex-row xl:items-center justify-between gap-8 relative">
            <div class="flex-1 space-y-4 md:space-y-0 md:flex md:items-center md:gap-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Signal <span class="text-wa-teal">Log</span></h3>
                    <p class="text-slate-500 font-medium text-sm">Real-time interaction matrix.</p>
                </div>
                
                <div class="relative group flex-1 max-w-md">
                    <input wire:model.live.debounce.300ms="filters.search" type="text"
                        class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-slate-100 dark:border-slate-800 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-200 shadow-sm focus:ring-wa-teal focus:border-wa-teal transition-all"
                        placeholder="SEARCH PHONE OR NAME...">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-wa-teal transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800/50 p-1.5 rounded-2xl border border-slate-100/50 dark:border-slate-800/50">
                    <select wire:model.live="filters.direction" 
                        class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-slate-500 focus:ring-0 cursor-pointer">
                        <option value="">All Streams</option>
                        <option value="inbound">Inbound Only</option>
                        <option value="outbound">Outbound Only</option>
                    </select>
                    <div class="h-4 w-px bg-slate-200 dark:bg-slate-700"></div>
                    <select wire:model.live="filters.status" 
                        class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-slate-500 focus:ring-0 cursor-pointer">
                        <option value="">Status Index</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="missed">Missed</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800/50 p-1.5 rounded-2xl border border-slate-100/50 dark:border-slate-800/50">
                    <input type="date" wire:model.live="filters.from_date" class="bg-transparent border-none text-[10px] font-black uppercase text-slate-500 focus:ring-0 cursor-pointer">
                    <span class="text-slate-300">→</span>
                    <input type="date" wire:model.live="filters.to_date" class="bg-transparent border-none text-[10px] font-black uppercase text-slate-500 focus:ring-0 cursor-pointer">
                </div>

                @if(array_filter($filters))
                    <button wire:click="clearFilters" 
                        class="p-4 bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-slate-900 rounded-2xl transition-all shadow-sm group">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 cursor-pointer hover:text-wa-teal transition-colors" wire:click="sortBy('direction')">Signal</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Collaborator</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 cursor-pointer hover:text-wa-teal transition-colors" wire:click="sortBy('status')">Integrity</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Temporal</th>
                        <th class="px-8 py-6 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Intelligence</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                    @forelse($calls as $call)
                        <tr wire:key="call-{{ $call->id }}" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex flex-col gap-2">
                                    <span class="inline-flex items-center w-fit px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border
                                        {{ $call->direction === 'inbound' ? 'bg-blue-500/10 text-blue-500 border-blue-500/20' : 'bg-purple-500/10 text-purple-500 border-purple-500/20' }}">
                                        {{ $call->direction === 'inbound' ? '↓ Inbound' : '↑ Outbound' }}
                                    </span>
                                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest opacity-60">CH: WA-VOICE</div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $call->contact->name ?? $call->from_number }}"
                                            alt="Avatar" class="w-12 h-12 rounded-[1.25rem] bg-slate-100 dark:bg-slate-800 object-cover shadow-sm group-hover:scale-105 transition-transform">
                                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-wa-teal border-2 border-white dark:border-slate-900 rounded-full"></div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                            {{ $call->contact->name ?? 'Unknown Identity' }}
                                        </div>
                                        <div class="text-[10px] text-slate-500 font-bold tabular-nums uppercase tracking-widest mt-1">
                                            {{ $call->direction === 'inbound' ? $call->from_number : $call->to_number }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                @php
                                    $statusColors = [
                                        'completed' => 'text-wa-teal bg-wa-teal/10 border-wa-teal/20',
                                        'failed' => 'text-rose-500 bg-rose-500/10 border-rose-500/20',
                                        'missed' => 'text-amber-500 bg-amber-500/10 border-amber-500/20',
                                    ];
                                    $color = $statusColors[$call->status] ?? 'text-slate-400 bg-slate-400/10 border-slate-400/20';
                                @endphp
                                <div class="flex flex-col gap-2">
                                    <span class="inline-flex w-fit px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $color }}">
                                        {{ ucfirst($call->status) }}
                                    </span>
                                    <div class="text-[10px] font-black text-slate-900 dark:text-white tabular-nums tracking-tighter">
                                        {{ $call->formatted_duration }}
                                    </div>
                                    @if($call->qualityMetric && $call->qualityMetric->network_quality_score)
                                        <div class="flex items-center gap-1 mt-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <div class="w-1 h-3 rounded-full {{ $i <= $call->qualityMetric->network_quality_score ? ($call->qualityMetric->network_quality_score <= 2 ? 'bg-rose-500' : 'bg-wa-teal') : 'bg-slate-200 dark:bg-slate-800' }}"></div>
                                            @endfor
                                            <span class="text-[8px] font-black text-slate-400 ml-1 uppercase">MOS: {{ $call->qualityMetric->network_quality_score }}.0</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                    {{ $call->created_at->format('M d, Y') }}
                                </div>
                                <div class="text-[10px] text-slate-500 font-bold tabular-nums uppercase tracking-widest mt-1">
                                    {{ $call->created_at->format('h:i:s A') }}
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex flex-col items-end gap-2 text-right">
                                    <div class="text-sm font-black text-wa-blue tracking-tighter">{{ $call->cost_formatted }}</div>
                                    <div class="flex items-center gap-2">
                                        @if($call->metadata['recording_url'] ?? false)
                                            <button @click="$dispatch('play-recording', { url: '{{ $call->metadata['recording_url'] }}' })"
                                                class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl hover:bg-wa-teal hover:text-slate-900 transition-all shadow-sm group/play">
                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        @endif
                                        <button class="px-4 py-2 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-wa-teal hover:text-slate-900 transition-all shadow-lg">
                                            Inspect Signal
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="p-6 bg-slate-50 dark:bg-slate-800 rounded-[2rem] mb-4">
                                        <svg class="w-12 h-12 text-slate-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Broadcast Silence</h3>
                                    <p class="text-slate-500 font-medium whitespace-nowrap">No telemetric signaling data found in this aperture.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($calls->hasPages())
            <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/20">
                {{ $calls->links() }}
            </div>
        @endif
    </div>
</div>
<div class="space-y-10">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-3 bg-wa-teal shadow-[0_0_15px_rgba(37,211,102,0.4)] text-slate-900 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Event <span class="text-wa-teal">Stream</span>
                </h1>
            </div>
            <p class="text-slate-500 font-medium">Real-time intelligence and velocity tracking for the system nervous
                system.</p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <div class="hidden md:flex flex-col items-end mr-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Stream Freshness</span>
                <span class="text-xs font-bold text-wa-teal flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-wa-teal animate-pulse"></span>
                    Recent Event: {{ $lastUpdated->diffForHumans() }}
                </span>
            </div>

            <div
                class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1.5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
                <button wire:click="refreshData" wire:loading.class="animate-spin"
                    class="p-2.5 text-slate-400 hover:text-wa-teal transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
                <div class="w-px h-6 bg-slate-100 dark:bg-slate-800"></div>
                <button wire:click="exportEvents"
                    class="flex items-center gap-2 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:text-wa-teal transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Stream
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Main Volume Card -->
        <div
            class="group relative bg-slate-900 dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-2xl border border-slate-800 overflow-hidden transition-all hover:scale-[1.02]">
            <div
                class="absolute -top-12 -right-12 w-32 h-32 bg-wa-teal opacity-10 rounded-full group-hover:scale-150 transition-transform duration-700">
            </div>

            <div class="relative">
                <div class="flex items-center justify-between mb-8">
                    <div class="p-4 rounded-2xl bg-white/5 text-wa-teal shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase font-black tracking-widest text-slate-500">Global
                            Volume</span>
                        <div class="text-2xl font-black text-white tabular-nums">{{ number_format($totalEvents) }}</div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span
                            class="text-3xl font-black text-white tracking-tight">{{ number_format($growth, 1) }}%</span>
                        <div
                            class="px-2 py-0.5 rounded-full {{ $growth >= 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }} text-[9px] font-black uppercase tracking-widest">
                            {{ $growth >= 0 ? 'Surge' : 'Dip' }}
                        </div>
                    </div>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-widest">24H Velocity Delta</div>
                </div>
            </div>
        </div>

        @foreach($eventStats->take(3) as $stat)
            @php
                $colors = ['blue' => 'from-wa-blue to-indigo-600', 'purple' => 'from-purple-500 to-fuchsia-600', 'teal' => 'from-wa-teal to-emerald-600'];
                $color = array_values($colors)[$loop->index] ?? $colors['blue'];
            @endphp
            <div
                class="group relative bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden transition-all hover:scale-[1.02]">
                <div
                    class="absolute -top-12 -right-12 w-32 h-32 bg-gradient-to-br {{ $color }} opacity-5 rounded-full group-hover:scale-150 transition-transform duration-700">
                </div>

                <div class="relative">
                    <div class="flex items-center justify-between mb-8">
                        <div
                            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 group-hover:bg-gradient-to-br group-hover:{{ $color }} group-hover:text-slate-900 transition-all duration-300">
                            @if($loop->index == 0)
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            @elseif($loop->index == 1)
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            @else
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            @endif
                        </div>
                        <div class="text-right">
                            <span
                                class="text-[10px] uppercase font-black tracking-widest text-slate-400">{{ class_basename($stat->event_type) }}</span>
                            <div class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">
                                {{ number_format($stat->count) }}</div>
                        </div>
                    </div>
                    <div class="bg-slate-100 dark:bg-slate-800 h-1.5 w-full rounded-full overflow-hidden">
                        <div class="bg-gradient-to-r {{ $color }} h-full transition-all duration-1000"
                            style="width: {{ min(100, ($stat->count / max(1, $totalEvents)) * 500) }}%"></div>
                    </div>
                    <p class="mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Interaction Points</p>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Trend Chart -->
        <div
            class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 blur-3xl rounded-full -mr-32 -mt-32"></div>

            <div class="relative">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Interaction <span class="text-wa-teal">Velocity</span>
                        </h3>
                        <p class="text-slate-500 font-medium">Daily frequency distribution across system modules</p>
                    </div>

                    <div
                        class="flex items-center gap-3 bg-slate-50 dark:bg-slate-800 p-1.5 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <select wire:model.live="filterDateRange"
                            class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 focus:ring-0 cursor-pointer">
                            <option value="1">24 Hours</option>
                            <option value="7">7 Days</option>
                            <option value="30">30 Days</option>
                            <option value="90">90 Days</option>
                        </select>
                    </div>
                </div>

                <div class="relative h-[320px] w-full -ml-4">
                    <canvas id="eventTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Distribution Card -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl border border-slate-50 dark:border-slate-800 relative overflow-hidden flex flex-col">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2">
                Event <span class="text-wa-teal">Mix</span>
            </h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-10">Composition index</p>

            <div class="flex-1 relative min-h-[250px] w-full flex items-center justify-center">
                <canvas id="eventDistributionChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span
                        class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($totalEvents) }}</span>
                    <span class="text-[8px] font-black uppercase text-slate-400 tracking-widest">Parsed</span>
                </div>
            </div>
        </div>

        <!-- Intelligence Log -->
        <div
            class="lg:col-span-3 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 blur-3xl rounded-full -mr-32 -mt-32"></div>

            <div
                class="px-8 py-8 border-b border-slate-50 dark:border-slate-800/50 flex flex-wrap items-center justify-between gap-6 relative">
                <div class="flex items-center gap-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Intelligence <span class="text-wa-teal">Stream</span></h3>
                        <p class="text-slate-500 font-medium text-sm">Real-time signal feed with deep inspection.</p>
                    </div>

                    <div
                        class="hidden lg:flex items-center gap-4 bg-slate-50/50 dark:bg-slate-800/50 p-1.5 rounded-2xl border border-slate-100/50 dark:border-slate-800/50">
                        <select wire:model.live="filterEventType"
                            class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-slate-500 focus:ring-0 cursor-pointer">
                            <option value="all">Every Event</option>
                            @foreach($eventStats as $stat)
                                <option value="{{ $stat->event_type }}">
                                    {{ strtoupper(str_replace('_', ' ', class_basename($stat->event_type))) }}</option>
                            @endforeach
                        </select>
                        <div class="h-4 w-px bg-slate-200 dark:bg-slate-700"></div>
                        <select wire:model.live="filterCategory"
                            class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-slate-500 focus:ring-0 cursor-pointer">
                            <option value="all">All Channels</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ strtoupper($category->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="relative group min-w-[300px]">
                    <input wire:model.live="searchTerm" type="text"
                        class="w-full pl-12 pr-4 py-3 bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-800 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-200 shadow-sm focus:ring-wa-teal focus:border-wa-teal transition-all"
                        placeholder="SEARCH STREAM SIGNALS...">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-wa-teal transition-colors"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div class="relative overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800/50">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                Timestamp</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                Classification</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                Activity Summary</th>
                            <th
                                class="px-8 py-6 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                Intelligence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                        @forelse($events as $event)
                            <tr wire:key="entry-{{ $event->id }}"
                                class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all duration-300">
                                <td class="px-8 py-6">
                                    <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                        {{ $event->occurred_at->format('M d, H:i') }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                                        {{ $event->occurred_at->diffForHumans() }}
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span
                                        class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border {{ \App\Services\EventPresenter::badgeClass($event) }}">
                                        {{ class_basename($event->event_type) }}
                                    </span>
                                    <div
                                        class="text-[9px] font-black text-slate-400 mt-2 uppercase tracking-widest opacity-60">
                                        {{ $event->source }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                        {{ \App\Services\EventPresenter::summary($event) }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-2">
                                        <div
                                            class="w-1.5 h-1.5 rounded-full bg-wa-blue shadow-[0_0_8px_rgba(52,183,241,0.5)]">
                                        </div>
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                            TRC: {{ Str::limit($event->trace_id ?? 'SYSTEM', 12) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button wire:click="viewEventDetails({{ $event->id }})"
                                        class="px-5 py-2.5 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-wa-teal hover:text-slate-900 hover:scale-105 transition-all shadow-lg active:scale-95">
                                        Analyze Signal
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="p-6 bg-slate-50 dark:bg-slate-800 rounded-[2rem] mb-4">
                                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.628.283a2 2 0 01-1.186.13l-2.014-.403a2 2 0 01-1.186-.13l-.628-.283a6 6 0 00-3.86-.517l-2.387.477a2 2 0 00-1.022.547l-.547 2.387a2 2 0 00.547 2.014l1.628 1.628a2 2 0 002.014.547l2.387-.547a6 6 0 003.86-.517l.628-.283a2 2 0 011.186-.13l2.014.403a2 2 0 011.186.13l.628.283a6 6 0 003.86.517l2.387-.547a2 2 0 002.014-.547l1.628-1.628a2 2 0 00.547-2.014l-.547-2.387z" />
                                            </svg>
                                        </div>
                                        <h3
                                            class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                            Stream Blocked</h3>
                                        <p class="text-slate-500 font-medium">No signal packets captured in this period.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($events->hasPages())
                <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/20">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Analysis Modal (Redesigned) -->
    @if($showDetailModal && $selectedEvent)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity animate-in fade-in duration-300"
                wire:click="closeDetailModal"></div>
            <div
                class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in zoom-in-95 duration-200">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-wa-teal to-wa-blue"></div>

                <div class="p-10 border-b border-slate-50 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Signal
                                <span class="text-wa-teal">Analysis</span></h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Transaction Node:
                                #{{ $selectedEvent->id }}</p>
                        </div>
                        <button wire:click="closeDetailModal"
                            class="p-4 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 rounded-2xl transition-all group">
                            <svg class="w-6 h-6 group-hover:rotate-90 transition-transform" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-10 space-y-8">
                    <div class="grid grid-cols-2 gap-8">
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-800">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Signal Origin
                            </h4>
                            @if($selectedEvent->contact)
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-lg bg-wa-teal/10 text-wa-teal shadow-inner">
                                        {{ substr($selectedEvent->contact->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900 dark:text-white uppercase tracking-tight text-sm">
                                            {{ $selectedEvent->contact->name ?? 'Unknown Actor' }}</div>
                                        <div class="text-[10px] font-bold text-slate-500 tracking-widest">
                                            {{ $selectedEvent->contact->phone_number }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A10.003 10.003 0 003.544 11V4h14.456l-1.077 10.158a10.001 10.001 0 01-2.903 6.136l-.043.045M9 11h.01m3.99 0h.01">
                                            </path>
                                        </svg>
                                    </div>
                                    <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Autonomous
                                        System Logic</span>
                                </div>
                            @endif
                        </div>
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-800">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Clock Data</h4>
                            <div class="space-y-1">
                                <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                    {{ $selectedEvent->created_at->format('M d, Y') }}</div>
                                <div class="text-xs font-mono font-bold text-wa-blue">
                                    {{ $selectedEvent->created_at->format('H:i:s.u P') }}</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Payload Integrity
                                Visualization</h4>
                            <span
                                class="text-[8px] font-black uppercase text-emerald-500 tracking-widest bg-emerald-500/10 px-2 py-0.5 rounded">Encrypted
                                at rest</span>
                        </div>
                        <div
                            class="bg-slate-900 rounded-[2.5rem] p-8 border border-slate-800 shadow-2xl relative overflow-hidden group">
                            <div class="absolute top-0 right-0 p-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-[10px] font-black text-wa-teal uppercase tracking-widest">JSON PKT</span>
                            </div>
                            <div class="overflow-x-auto overflow-y-auto max-h-96 custom-scrollbar">
                                <pre
                                    class="text-emerald-400/90 font-mono text-xs leading-relaxed"><code>{{ json_encode($selectedEvent->event_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-10 py-8 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center relative">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 italic">Security Access
                        Level: ROOT</div>
                    <button wire:click="closeDetailModal"
                        class="px-10 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl transition-all hover:bg-wa-teal hover:text-slate-900 hover:scale-105 active:scale-95">
                        Acknowledge Signal
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
                            borderWidth: 4,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#25D366',
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
                                padding: 16,
                                cornerRadius: 16,
                                borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)',
                                borderWidth: 1,
                                displayColors: false
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
                                    generateLabels: (chart) => {
                                        const data = chart.data;
                                        return data.labels.map((label, i) => ({
                                            text: label.toUpperCase(),
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            hidden: isNaN(data.datasets[0].data[i]),
                                            index: i
                                        }));
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: isDark ? '#1e293b' : '#fff',
                                padding: 16,
                                cornerRadius: 16,
                                titleFont: { size: 12, weight: '900' },
                                bodyFont: { size: 11, weight: '700' },
                            }
                        }
                    }
                });
            }

            initCharts();

            Livewire.on('refreshCharts', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                initCharts(data.chartData, data.distData);
            });
        });
    </script>
</div>
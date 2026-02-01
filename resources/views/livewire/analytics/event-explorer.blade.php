<div class="space-y-10">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start gap-10">
        <!-- Left: Title & Subtitle -->
        <div class="max-w-xl">
            <h1 class="text-5xl font-black tracking-tight uppercase leading-none mb-4">
                <span class="text-slate-900 dark:text-white">Event</span> 
                <span class="text-wa-teal">Explorer</span>
            </h1>
            <p class="text-slate-500 font-medium text-lg leading-relaxed">
                System nervous system observability & real-time trace tracking.
            </p>
            @if($filterTraceId)
                <div class="mt-4 flex items-center gap-2 bg-indigo-500/10 text-indigo-500 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border border-indigo-500/20 w-fit">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                    Trace: {{ Str::limit($filterTraceId, 12) }}
                    <button wire:click="$set('filterTraceId', '')" class="hover:text-indigo-700 transition ml-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif
        </div>

        <!-- Right: Controls Section -->
        <div class="flex flex-col items-end gap-6 shrink-0">
            <!-- Row 1: State & Filters -->
            <div class="flex items-center gap-8">
                <div class="flex flex-col items-end">
                    <span class="text-[10px] font-black uppercase tracking-[0.1em] text-slate-400 mb-0.5">Observability State</span>
                    <span class="text-sm font-bold text-wa-teal flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-wa-teal animate-pulse"></span>
                        Real-time Tracking Active
                    </span>
                </div>

                <div class="flex items-center bg-white dark:bg-slate-900 px-6 py-4 rounded-[1.25rem] shadow-sm border border-slate-100 dark:border-slate-800 gap-6">
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="showNoise" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-wa-teal"></div>
                        </label>
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Noise</span>
                    </div>

                    <div class="w-px h-6 bg-slate-100 dark:bg-slate-800"></div>

                    <select wire:model.live="filterModule" 
                        class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 focus:ring-0 cursor-pointer p-0 pr-8">
                        <option value="">All Sources</option>
                        @foreach($modules as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>

                    <div class="w-px h-6 bg-slate-100 dark:bg-slate-800"></div>

                    <select wire:model.live="filterCategory"
                        class="bg-transparent border-none text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 focus:ring-0 cursor-pointer p-0 pr-8">
                        <option value="">All Categories</option>
                        <option value="business">Business</option>
                        <option value="operational">Operational</option>
                        <option value="debug">Debug</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Search Bar -->
            <div class="flex items-center gap-4">
                <div class="relative group w-[340px]">
                    <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-wa-teal transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="SEARCH EVENTS..." 
                        class="pl-14 pr-6 py-4 bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 rounded-[1.25rem] text-[10px] font-black uppercase tracking-[0.1em] text-slate-600 dark:text-slate-300 focus:ring-wa-teal focus:border-wa-teal shadow-sm transition-all w-full" />
                </div>

                @if($search || $filterModule || $filterCategory || $filterTraceId || $showNoise)
                    <button wire:click="clearFilters" 
                        class="p-4 bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-slate-900 rounded-[1.25rem] transition-all shadow-sm group">
                        <svg class="w-6 h-6 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Events Content -->
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden relative">
        <div class="absolute top-0 right-0 w-64 h-64 bg-wa-teal/5 blur-3xl rounded-full -mr-32 -mt-32"></div>

        <div class="relative overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-800/50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Timestamp
                        </th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                            Classification</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Event
                            Signature</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Activity
                            Summary</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Source
                        </th>
                        <th
                            class="px-8 py-6 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                            Control</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                    @forelse($events as $event)
                        @php
                            $categoryColors = [
                                'business' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
                                'operational' => 'text-wa-blue bg-wa-blue/10 border-wa-blue/20',
                                'debug' => 'text-slate-400 bg-slate-400/10 border-slate-400/20',
                            ];
                            $catStyle = $categoryColors[$event->category] ?? $categoryColors['debug'];
                        @endphp
                        <tr
                            class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all duration-300 cursor-default">
                            <td class="px-8 py-5">
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $event->occurred_at->diffForHumans() }}</span>
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $event->occurred_at->format('H:i:s.u') }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <span
                                    class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $catStyle }}">
                                    {{ $event->category }}
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <span class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight"
                                    title="{{ $event->event_type }}">
                                    {{ Str::limit(class_basename($event->event_type), 25) }}
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <p class="text-sm text-slate-500 dark:text-slate-400 font-medium max-w-xs truncate">
                                    {{ \App\Services\EventPresenter::summary($event) }}
                                </p>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-wa-teal shadow-[0_0_8px_rgba(37,211,102,0.5)]">
                                    </div>
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-500">{{ $event->source }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex items-center justify-end gap-3">
                                    @if($event->trace_id)
                                        <button wire:click="viewTrace('{{ $event->trace_id }}')"
                                            class="p-2 text-slate-400 hover:text-wa-blue hover:bg-wa-blue/10 rounded-xl transition-all"
                                            title="View Full Trace">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                        </button>
                                    @endif
                                    <button wire:click="showDetails({{ $event->id }})"
                                        class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-wa-teal hover:text-slate-900 transition-all shadow-sm">
                                        Inspect
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="p-6 bg-slate-50 dark:bg-slate-800 rounded-[2rem] mb-4">
                                        <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.628.283a2 2 0 01-1.186.13l-2.014-.403a2 2 0 01-1.186-.13l-.628-.283a6 6 0 00-3.86-.517l-2.387.477a2 2 0 00-1.022.547l-.547 2.387a2 2 0 00.547 2.014l1.628 1.628a2 2 0 002.014.547l2.387-.547a6 6 0 003.86-.517l.628-.283a2 2 0 011.186-.13l2.014.403a2 2 0 011.186.13l.628.283a6 6 0 003.86.517l2.387-.547a2 2 0 002.014-.547l1.628-1.628a2 2 0 00.547-2.014l-.547-2.387z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                        System Silent</h3>
                                    <p class="text-slate-500 font-medium">No events found matching current criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-8 py-6 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/20">
            {{ $events->links() }}
        </div>
    </div>

    <!-- Event Detail Slide-over / Modal (Redesigned) -->
    @if($showTraceModal && $selectedEvent)
        <div class="fixed inset-0 z-[100] overflow-hidden" x-data="{ show: false }"
            x-init="setTimeout(() => show = true, 50)">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity duration-500" x-show="show"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" wire:click="closeDetails"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10 sm:pl-16">
                <div class="w-screen max-w-4xl transform transition-all duration-500 ease-in-out" x-show="show"
                    x-transition:enter="transform transition ease-in-out duration-500"
                    x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">

                    <div
                        class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl rounded-l-[3rem] border-l border-slate-100 dark:border-slate-800 overflow-hidden relative">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-wa-teal to-wa-blue"></div>

                        <!-- Modal Header -->
                        <div class="px-8 py-10 shrink-0">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <h2
                                            class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                            Event <span class="text-wa-teal">Intelligence</span>
                                        </h2>
                                        <span
                                            class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-full text-[10px] font-black uppercase tracking-widest">
                                            #{{ $selectedEvent->id }}
                                        </span>
                                    </div>
                                    <p class="text-slate-500 font-medium">{{ $selectedEvent->event_type }}</p>
                                </div>
                                <button wire:click="closeDetails"
                                    class="p-4 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 rounded-[1.5rem] transition-all group">
                                    <svg class="w-6 h-6 group-hover:rotate-90 transition-transform" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-8 flex items-center gap-6">
                                <div
                                    class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl flex-1 border border-slate-100 dark:border-slate-800">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-1">Trace
                                        ID</span>
                                    <span
                                        class="text-xs font-mono font-bold text-wa-blue">{{ $selectedEvent->trace_id ?? 'SYSTEM_SPAN' }}</span>
                                </div>
                                <div
                                    class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl flex-1 border border-slate-100 dark:border-slate-800">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-1">Status</span>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-wa-teal animate-pulse"></span>
                                        <span
                                            class="text-xs font-black uppercase tracking-widest text-slate-700 dark:text-slate-200">Processing
                                            Success</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Body -->
                        <div class="flex-1 overflow-hidden flex">
                            <!-- Left: Trace Navigation -->
                            <div class="w-80 border-r border-slate-50 dark:border-slate-800/50 flex flex-col">
                                <div class="px-8 py-4 border-b border-slate-50 dark:border-slate-800/50">
                                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Trace
                                        Timeline</h4>
                                </div>
                                <div class="flex-1 overflow-y-auto px-4 py-6 scrollbar-hide">
                                    <div class="space-y-1 relative">
                                        <div
                                            class="absolute left-[27px] top-4 bottom-4 w-px bg-slate-100 dark:bg-slate-800">
                                        </div>

                                        @foreach($traceEvents as $te)
                                            <button wire:click="showDetails({{ $te->id }})"
                                                class="w-full text-left relative pl-12 py-3 rounded-2xl transition-all {{ $te->id === $selectedEvent->id ? 'bg-wa-teal/10 border-wa-teal/20' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                                                <!-- Node Indicator -->
                                                <div class="absolute left-6 top-1/2 -translate-x-1/2 -translate-y-1/2">
                                                    <div
                                                        class="w-3 h-3 rounded-full border-2 border-white dark:border-slate-900 transition-all {{ $te->id === $selectedEvent->id ? 'bg-wa-teal scale-125 shadow-[0_0_10px_rgba(37,211,102,0.5)]' : 'bg-slate-200 dark:bg-slate-700' }}">
                                                    </div>
                                                </div>

                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-xs font-black uppercase tracking-tight {{ $te->id === $selectedEvent->id ? 'text-wa-teal' : 'text-slate-700 dark:text-slate-300' }}">
                                                        {{ class_basename($te->event_type) }}
                                                    </span>
                                                    <span class="text-[9px] font-bold text-slate-400 mt-0.5">
                                                        {{ $te->occurred_at->format('H:i:s.u') }}
                                                    </span>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Inspector -->
                            <div class="flex-1 overflow-y-auto p-8 scrollbar-hide bg-slate-50/30 dark:bg-slate-900/30">
                                <div x-data="{ tab: 'payload' }">
                                    <div
                                        class="flex items-center gap-2 bg-white dark:bg-slate-800 p-1.5 rounded-[1.25rem] shadow-sm border border-slate-100 dark:border-slate-800 mb-8 w-fit">
                                        @foreach(['payload' => 'Payload', 'meta' => 'Meta', 'actor' => 'Context'] as $key => $label)
                                            <button @click="tab = '{{ $key }}'"
                                                class="px-6 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all duration-300"
                                                :class="tab === '{{ $key }}' ? 'bg-wa-teal text-slate-900 shadow-lg shadow-wa-teal/20' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>

                                    <div class="space-y-6">
                                        <div x-show="tab === 'payload'" x-transition>
                                            <div class="bg-slate-900 rounded-3xl p-8 relative overflow-hidden group">
                                                <div
                                                    class="absolute top-0 right-0 p-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <span
                                                        class="text-[10px] font-black text-wa-teal uppercase tracking-widest">JSON
                                                        OBJECT</span>
                                                </div>
                                                <pre
                                                    class="text-sm font-mono text-emerald-400/90 leading-relaxed overflow-x-auto"><code>{{ json_encode($selectedEvent->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                            </div>
                                        </div>

                                        <div x-show="tab === 'meta'" x-transition>
                                            <div class="bg-slate-900 rounded-3xl p-8 relative">
                                                <pre
                                                    class="text-sm font-mono text-wa-blue/90 leading-relaxed overflow-x-auto"><code>{{ json_encode($selectedEvent->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                            </div>
                                        </div>

                                        <div x-show="tab === 'actor'" x-transition>
                                            <div class="grid grid-cols-1 gap-4">
                                                <div
                                                    class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-50 dark:border-slate-700/50 shadow-sm">
                                                    <span
                                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Authenticated
                                                        Actor</span>
                                                    <div class="flex items-center gap-3">
                                                        <div
                                                            class="w-10 h-10 rounded-full bg-wa-teal/10 flex items-center justify-center text-wa-teal">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2.5"
                                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                        </div>
                                                        <span
                                                            class="text-sm font-bold text-slate-900 dark:text-white">{{ $selectedEvent->actor_id ?? 'SYSTEM_ROOT' }}</span>
                                                    </div>
                                                </div>
                                                <div
                                                    class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-50 dark:border-slate-700/50 shadow-sm">
                                                    <span
                                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Execution
                                                        Span</span>
                                                    <div class="flex items-center gap-3">
                                                        <div
                                                            class="w-10 h-10 rounded-full bg-wa-blue/10 flex items-center justify-center text-wa-blue">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                            </svg>
                                                        </div>
                                                        <span
                                                            class="text-xs font-mono font-bold text-slate-900 dark:text-white">{{ $selectedEvent->span_id ?? '-' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div
                            class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-white dark:bg-slate-900 shrink-0">
                            <div class="flex items-center justify-between">
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 italic">
                                    Secure Observability Node v1.0.4
                                </div>
                                <button wire:click="closeDetails"
                                    class="px-8 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-wa-teal hover:text-slate-900 hover:scale-105 transition-all shadow-xl">
                                    Acknowledge & Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
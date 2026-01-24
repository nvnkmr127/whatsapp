<div
    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
    <!-- Module Selector Tabs -->
    <div class="flex flex-wrap items-center gap-2 mb-10 pb-6 border-b border-slate-50 dark:border-slate-800">
        <button wire:click="setModule('inbox')"
            class="px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all {{ $activeModule === 'inbox' ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}">
            Shared Inbox
        </button>
        <button wire:click="setModule('broadcast')"
            class="px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all {{ $activeModule === 'broadcast' ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}">
            Broadcasts
        </button>
        <button wire:click="setModule('automation')"
            class="px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all {{ $activeModule === 'automation' ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}">
            Automations
        </button>
        <button wire:click="setModule('template')"
            class="px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all {{ $activeModule === 'template' ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}">
            Templates
        </button>
        <button wire:click="setModule('commerce')"
            class="px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all {{ $activeModule === 'commerce' ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}">
            Commerce
        </button>
        <button wire:click="setModule('compliance')"
            class="px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all {{ $activeModule === 'compliance' ? 'bg-wa-teal text-white shadow-lg shadow-wa-teal/20' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200' }}">
            Risk & Compliance
        </button>
    </div>

    <!-- Insights Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 relative">
        <div wire:loading
            class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-10 flex items-center justify-center rounded-2xl">
            <div class="w-8 h-8 border-4 border-wa-teal/20 border-t-wa-teal rounded-full animate-spin"></div>
        </div>

        @foreach($stats as $stat)
            <div class="flex flex-col">
                <div class="flex items-center justify-between mb-2">
                    <span
                        class="text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">{{ $stat['label'] }}</span>

                    <!-- Status Indicator Dot -->
                    <div class="flex items-center gap-1.5">
                        <span
                            class="text-[8px] font-black uppercase tracking-tighter {{ $stat['status'] === 'success' ? 'text-wa-teal' : ($stat['status'] === 'problem' ? 'text-rose-500' : 'text-slate-300') }}">
                            {{ $stat['status'] === 'success' ? 'Optimal' : ($stat['status'] === 'problem' ? 'Attention' : 'Stable') }}
                        </span>
                        <span class="relative flex h-2 w-2">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $stat['status'] === 'success' ? 'bg-wa-teal' : ($stat['status'] === 'problem' ? 'bg-rose-400' : 'bg-slate-300') }}"></span>
                            <span
                                class="relative inline-flex rounded-full h-2 w-2 {{ $stat['status'] === 'success' ? 'bg-wa-teal' : ($stat['status'] === 'problem' ? 'bg-rose-500' : 'bg-slate-400') }}"></span>
                        </span>
                    </div>
                </div>

                <div class="flex items-baseline gap-3">
                    <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $stat['value'] }}</span>

                    @if($stat['trend'] === 'up')
                        <svg class="w-4 h-4 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    @elseif($stat['trend'] === 'down')
                        <svg class="w-4 h-4 text-rose-500 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                    @endif
                </div>

                <div class="mt-4 h-1.5 w-full bg-slate-50 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full {{ $stat['status'] === 'success' ? 'bg-wa-teal' : ($stat['status'] === 'problem' ? 'bg-rose-500' : 'bg-slate-400') }} opacity-20"
                        style="width: 100%"></div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Contextual Suggestion -->
    <!-- Contextual Insights Layer -->
    @if(count($insights) > 0)
        <div class="mt-8 flex flex-col gap-3">
            @foreach($insights as $insight)
                <div class="p-4 rounded-xl border flex items-start gap-4 transition-all
                    {{ $insight['type'] === 'critical' ? 'bg-rose-50 border-rose-100 dark:bg-rose-900/20 dark:border-rose-800' : 
                       ($insight['type'] === 'warning' ? 'bg-amber-50 border-amber-100 dark:bg-amber-900/20 dark:border-amber-800' : 
                       ($insight['type'] === 'money' ? 'bg-emerald-50 border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-800' :
                       'bg-slate-50 border-slate-100 dark:bg-slate-800/50 dark:border-slate-800')) }}">
                    
                    <div class="p-2 rounded-lg shrink-0
                        {{ $insight['type'] === 'critical' ? 'bg-rose-100 text-rose-600 dark:bg-rose-800 dark:text-rose-200' : 
                           ($insight['type'] === 'warning' ? 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200' : 
                           ($insight['type'] === 'money' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-800 dark:text-emerald-200' :
                           'bg-white text-wa-teal shadow-sm dark:bg-slate-800 dark:text-wa-teal')) }}">
                        
                        @if($insight['type'] === 'critical' || $insight['type'] === 'warning')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        @elseif($insight['type'] === 'money')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-4">
                            <h4 class="text-[10px] font-black uppercase tracking-widest mb-1
                                {{ $insight['type'] === 'critical' ? 'text-rose-700 dark:text-rose-300' : 
                                   ($insight['type'] === 'warning' ? 'text-amber-700 dark:text-amber-300' : 
                                   ($insight['type'] === 'money' ? 'text-emerald-700 dark:text-emerald-300' :
                                   'text-slate-900 dark:text-white')) }}">
                                {{ $insight['type'] === 'money' ? 'Revenue Opportunity' : ($insight['type'] === 'critical' ? 'Urgent Action Required' : 'Optimization Signal') }}
                            </h4>
                            @if(isset($insight['action_label']))
                                <a href="{{ $insight['action_url'] }}" class="hidden sm:flex text-[10px] font-bold items-center gap-1 hover:underline
                                    {{ $insight['type'] === 'critical' ? 'text-rose-600' : 'text-wa-teal' }}">
                                    {{ $insight['action_label'] }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </a>
                            @endif
                        </div>
                        
                        <p class="text-xs font-medium leading-relaxed
                            {{ $insight['type'] === 'critical' ? 'text-rose-600 dark:text-rose-200' : 
                               ($insight['type'] === 'warning' ? 'text-amber-600 dark:text-amber-200' : 
                               ($insight['type'] === 'money' ? 'text-emerald-600 dark:text-emerald-200' :
                               'text-slate-500 dark:text-slate-400')) }}">
                            {!! $insight['message'] !!}
                        </p>
                        
                        @if(isset($insight['action_label']))
                            <a href="{{ $insight['action_url'] }}" class="mt-3 flex sm:hidden text-[10px] font-bold items-center gap-1 hover:underline
                                {{ $insight['type'] === 'critical' ? 'text-rose-600' : 'text-wa-teal' }}">
                                {{ $insight['action_label'] }}
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State / Clean Bill of Health -->
        <div class="mt-8 p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800/50 flex items-center justify-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">All systems optimal. No alerts.</span>
        </div>
    @endif
</div>
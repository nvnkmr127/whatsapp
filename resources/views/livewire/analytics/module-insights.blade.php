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
    <div
        class="mt-10 p-4 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100 dark:border-slate-800/50 flex items-start gap-4">
        <div class="p-2 bg-white dark:bg-slate-900 rounded-xl shadow-sm">
            <svg class="w-5 h-5 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-900 dark:text-white mb-1">Module
                Intelligence</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
                @if($activeModule === 'inbox')
                    High resolution times detected. Consider enabling <strong>Auto-Close</strong> for inactive conversations
                    to improve ART baseline.
                @elseif($activeModule === 'broadcast')
                    Read rates are performing above industry average. You can increase the frequency of high-performing
                    templates for better ROI.
                @elseif($activeModule === 'automation')
                    Flow completion is trailing. Check <strong>Node #4</strong> in the most active automation; customers are
                    dropping off at the payment link.
                @elseif($activeModule === 'template')
                    Your media-to-text ratio is healthy. Meta prefers interactive templates with buttons; consider adding
                    'Quick Reply' to your transactional alerts.
                @elseif($activeModule === 'commerce')
                    Revenue is scaling. Payment pendency is slighty high; enabling <strong>Auto-Payment Reminders</strong>
                    could improve liquidation by 14%.
                @elseif($activeModule === 'compliance')
                    Monitoring four critical risk vectors. <strong>Opt-out exceeding 2%</strong> triggers cooldown
                    protocols.
                    <strong>Quality Score</strong> dropping to 'YELLOW' will throttle broadcast volume by 50% automatically.
                @endif
            </p>
        </div>
    </div>
</div>
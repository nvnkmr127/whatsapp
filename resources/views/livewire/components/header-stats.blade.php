<div>
    <!-- Trigger Button -->
    <button wire:click="openModal"
        class="p-2.5 rounded-2xl text-slate-400 hover:text-wa-primary hover:bg-white dark:hover:bg-slate-900 shadow-none hover:shadow-lg transition-all duration-300 focus:outline-none group relative">
        <svg class="w-5 h-5 group-hover:scale-110 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <!-- Tooltip -->
        <span class="absolute top-full mt-2 left-1/2 -translate-x-1/2 px-2 py-1 bg-slate-800 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50">
            Usage Stats
        </span>
    </button>

    <!-- Modal -->
    <x-dialog-modal wire:model.live="showModal" maxWidth="2xl">
        <x-slot name="title">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="p-2 bg-wa-primary/10 rounded-xl text-wa-primary">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black uppercase tracking-tight text-slate-900 dark:text-white">
                        Usage Statistics
                    </h3>
                    <p class="text-xs font-medium text-slate-500">
                        Current billing period allocation vs usage
                    </p>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-4">
                @foreach($stats as $key => $stat)
                    @php
                        // Calculate percentage safely
                        $limit = $stat['limit'] ?? 0;
                        $usage = $stat['usage'] ?? 0;
                        $percent = $limit > 0 ? min(($usage / $limit) * 100, 100) : 0;
                        
                        // Determine color based on usage intensity
                        $colorClass = 'bg-wa-primary'; // Default Teal
                        if ($limit > 0) {
                            if ($percent >= 90) $colorClass = 'bg-rose-500';
                            elseif ($percent >= 75) $colorClass = 'bg-amber-500';
                        }
                    @endphp

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 transition-all hover:shadow-sm">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="text-[11px] font-black uppercase tracking-widest text-slate-500">{{ $stat['label'] }}</h4>
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                                {{ ucfirst($stat['type'] ?? 'Monthly') }}
                            </span>
                        </div>
                        
                        <div class="flex items-baseline gap-1.5 mb-3">
                            <span class="text-2xl font-black text-slate-900 dark:text-white">
                                {{ number_format($usage) }}
                            </span>
                            <span class="text-xs font-bold text-slate-400">
                                / {{ $limit > 0 ? number_format($limit) : '∞' }}
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full {{ $colorClass }} transition-all duration-500 rounded-full" style="width: {{ $limit > 0 ? $percent : 100 }}%"></div>
                        </div>
                        
                        @if($limit > 0)
                            <div class="mt-1 text-right">
                                <span class="text-[10px] font-bold {{ $percent > 90 ? 'text-rose-500' : 'text-slate-400' }}">
                                    {{ number_format($percent, 1) }}% Used
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-between w-full items-center pt-2">
                <a href="{{ route('billing') }}" class="text-xs font-black uppercase tracking-wide text-wa-primary hover:text-wa-primary-dark hover:underline flex items-center gap-1 transition-colors">
                    View Full Billing Dashboard 
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <x-secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled">
                    {{ __('Close') }}
                </x-secondary-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
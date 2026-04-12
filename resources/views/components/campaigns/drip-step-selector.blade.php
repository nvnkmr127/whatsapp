@props(['steps' => [], 'current' => 0])

<div class="flex items-center gap-4 overflow-x-auto pb-4 no-scrollbar">
    {{-- Initial Step --}}
    <button type="button" 
        wire:click="selectDripStep(0)"
        class="flex-shrink-0 flex items-center gap-3 px-6 py-4 rounded-2xl border-2 transition-all {{ $current == 0 ? 'border-wa-teal bg-wa-teal/5 ring-4 ring-wa-teal/10' : 'border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 group' }}">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black {{ $current == 0 ? 'bg-wa-teal text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 group-hover:bg-slate-200' }}">1</div>
        <div class="text-left">
            <div class="text-[10px] font-black uppercase tracking-widest leading-none mb-1 {{ $current == 0 ? 'text-wa-teal' : 'text-slate-400' }}">Main Step</div>
            <div class="text-sm font-black text-slate-900 dark:text-white leading-none">Initial Outreach</div>
        </div>
    </button>

    {{-- Drip Steps --}}
    @foreach($steps as $index => $step)
        <div class="flex items-center gap-4 flex-shrink-0 animate-in slide-in-from-left-4 duration-300" style="animation-delay: {{ $index * 100 }}ms">
            <svg class="w-5 h-5 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>

            <div class="relative group">
                <button type="button" 
                    wire:click="selectDripStep({{ $index + 1 }})"
                    class="flex items-center gap-3 px-6 py-4 rounded-2xl border-2 transition-all {{ $current == $index + 1 ? 'border-wa-teal bg-wa-teal/5 ring-4 ring-wa-teal/10' : 'border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black {{ $current == $index + 1 ? 'bg-wa-teal text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">{{ $index + 2 }}</div>
                    <div class="text-left mr-4">
                        <div class="text-[10px] font-black uppercase tracking-widest leading-none mb-1 {{ $current == $index + 1 ? 'text-wa-teal' : 'text-slate-400' }}">
                            After 
                            @if($step['delay_minutes'] >= 1440)
                                {{ round($step['delay_minutes'] / 1440, 1) }}d
                            @elseif($step['delay_minutes'] >= 60)
                                {{ round($step['delay_minutes'] / 60, 1) }}h
                            @else
                                {{ $step['delay_minutes'] }}m
                            @endif
                        </div>
                        <div class="text-sm font-black text-slate-900 dark:text-white leading-none line-clamp-1 max-w-[120px]">
                            {{ !empty($step['template_name']) ? str_replace('_', ' ', $step['template_name']) : 'Follow-up' }}
                        </div>
                    </div>
                </button>
                
                <button type="button"
                    wire:click="removeDripStep({{ $index }})"
                    class="absolute -top-2 -right-2 w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg hover:scale-110 active:scale-95 z-20">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endforeach

    {{-- Add Button --}}
    <button type="button" 
        wire:click="addDripStep"
        class="flex-shrink-0 flex items-center gap-2 px-6 py-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 text-slate-400 hover:text-wa-teal hover:bg-wa-teal/5 border-2 border-dashed border-slate-200 dark:border-slate-700 transition-all font-black uppercase tracking-widest text-[10px]">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        Add Step
    </button>
</div>

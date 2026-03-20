<div class="space-y-8 animate-in fade-in duration-500">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-teal-100 text-wa-teal rounded-lg dark:bg-teal-500/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Template <span class="text-wa-teal">Heatmap</span>
                </h1>
            </div>
            <p class="text-slate-500 font-medium">{{ __('Identify the best time and day to send each template for maximum read rates.') }}</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('analytics') }}"
                class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 font-bold text-xs uppercase tracking-widest rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Analytics
            </a>
            <button wire:click="loadHeatmap"
                class="p-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 transition-all"
                title="Refresh">
                <svg class="w-4 h-4" wire:loading.class="animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Sub-nav tabs --}}
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-0">
        <a href="{{ route('analytics') }}"
            class="px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-t-xl border border-transparent transition-all text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
            {{ __('Overview') }}
        </a>
        <a href="{{ route('analytics.events') }}"
            class="px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-t-xl border border-transparent transition-all text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
            {{ __('Events') }}
        </a>
        <a href="{{ route('analytics.explorer') }}"
            class="px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-t-xl border border-transparent transition-all text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
            {{ __('Explorer') }}
        </a>
        <span class="shrink-0 px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-t-xl bg-white dark:bg-slate-900 border border-b-white dark:border-slate-700 dark:border-b-slate-900 -mb-px text-wa-teal">
            {{ __('Template Heatmap') }}
        </span>
        <a href="{{ route('analytics.cohorts') }}"
            class="shrink-0 px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-t-xl border border-transparent transition-all text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
            {{ __('Cohort Analysis') }}
        </a>
    </div>

    {{-- No data state --}}
    @if(empty($heatmap) || empty($heatmap['rows']))
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="p-4 bg-slate-100 dark:bg-slate-800 rounded-2xl mb-4">
                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7" />
                </svg>
            </div>
            <h3 class="text-base font-black text-slate-700 dark:text-slate-200">{{ __('No template send data yet') }}</h3>
            <p class="text-sm text-slate-400 mt-1 max-w-sm">
                {{ __('Send campaigns using approved templates. Heatmap data populates after the first sends within the last 90 days.') }}
            </p>
            <a href="{{ route('campaigns.index') }}"
                class="mt-6 inline-flex items-center gap-2 px-6 py-3 bg-wa-teal text-white font-black text-xs uppercase tracking-widest rounded-2xl shadow hover:opacity-90 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('New Campaign') }}
            </a>
        </div>

    @else

        {{-- Heatmap Card --}}
        <div class="rounded-2xl border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">

            {{-- Card Header --}}
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 p-6 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ __('Template Performance Heatmap') }}</p>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">
                        {{ $heatmap['selected_template_name'] ?? __('Top Template') }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ __('Read rates by send hour & weekday — last :days days', ['days' => $heatmap['window_days'] ?? 90]) }}
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:items-end">
                    @if(!empty($heatmap['template_options']))
                        <div class="w-full sm:w-80">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 block">Select Template</label>
                            <select wire:model.live="selectedTemplateId"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold px-3 py-2.5 focus:ring-2 focus:ring-wa-teal outline-none">
                                @foreach($heatmap['template_options'] as $opt)
                                    <option value="{{ $opt['id'] }}">
                                        {{ $opt['name'] }} ({{ number_format($opt['sent']) }} sends · {{ number_format($opt['read_rate'], 1) }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                        <span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">Baseline</span>
                            {{ number_format($heatmap['baseline_rate'] ?? 0, 1) }}%
                        </span>
                        <span class="text-slate-300 dark:text-slate-700">|</span>
                        <span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">Total Sends</span>
                            {{ number_format($heatmap['sample_size'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Low sample warning --}}
            @if(!empty($heatmap['is_low_sample']))
                <div class="mx-6 mt-5 p-3 rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-200">
                        {{ __('Limited sample: :count sends.', ['count' => number_format($heatmap['sample_size'] ?? 0)]) }}
                        {{ __('Add :gap+ more sends for stronger timing reliability.', ['gap' => number_format($heatmap['sample_gap'] ?? 0)]) }}
                    </p>
                    <span class="shrink-0 text-[10px] font-black uppercase tracking-wider px-2 py-1 rounded-md bg-amber-100 text-amber-700 dark:bg-amber-800/40 dark:text-amber-300">
                        {{ __('Low data confidence') }}
                    </span>
                </div>
            @endif

            {{-- Grid --}}
            <div class="overflow-x-auto px-6 pt-5 pb-4">
                <div class="min-w-[1100px]">

                    {{-- Hour labels --}}
                    <div class="grid grid-cols-[72px_repeat(24,minmax(0,1fr))] gap-1 mb-2">
                        <div></div>
                        @for($h = 0; $h < 24; $h++)
                            <div class="text-[9px] font-bold text-center text-slate-400 dark:text-slate-500">
                                {{ sprintf('%02d', $h) }}
                            </div>
                        @endfor
                    </div>

                    {{-- Rows --}}
                    @foreach($heatmap['rows'] as $row)
                        <div class="grid grid-cols-[72px_repeat(24,minmax(0,1fr))] gap-1 mb-1">
                            <div class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-300 flex items-center py-1">
                                {{ $row['day_label'] }}
                            </div>

                            @foreach($row['cells'] as $cell)
                                @php
                                    $hasData = ($cell['sent'] ?? 0) > 0;
                                    $alpha = $hasData ? (0.08 + (($cell['intensity'] ?? 0) / 100) * 0.80) : 0;
                                    $bg = $hasData 
                                        ? 'background-color: rgba(13, 148, 136, ' . number_format($alpha, 2, '.', '') . ');'
                                        : 'background-color: transparent;';
                                    
                                    $isBest = $hasData && !empty($heatmap['best_slot'])
                                        && $heatmap['best_slot']['day_label'] === $row['day_label']
                                        && $heatmap['best_slot']['hour_label']  === $cell['hour_label'];
                                @endphp
                                <div class="h-8 rounded-md transition-transform {{ $hasData ? 'hover:scale-110 cursor-default shadow-sm' : '' }} {{ $isBest ? 'ring-2 ring-wa-teal ring-offset-1 dark:ring-offset-slate-900 z-10' : ($hasData ? 'border border-white/20 dark:border-slate-800/40' : 'border border-slate-50 dark:border-slate-800/20') }}"
                                    style="{{ $bg }}"
                                    title="{{ $hasData ? ($row['day_label'] . ' ' . $cell['hour_label'] . ': ' . number_format($cell['read_rate'], 1) . '% ' . __('read') . ' (' . $cell['read'] . '/' . $cell['sent'] . ')') : __('No data') }}">
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Legend + Best Slot --}}
            <div class="px-6 pb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-[11px] text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-2">
                    <span>Lower read rate</span>
                    <div class="h-2 w-24 rounded-full bg-gradient-to-r from-slate-200 to-teal-600/80"></div>
                    <span>Higher read rate</span>
                </div>

                @if(!empty($heatmap['best_slot']))
                    @php $bs = $heatmap['best_slot']; @endphp
                    <div class="flex items-center gap-2 flex-wrap">
                        <span>{{ __('Best slot:') }}</span>
                        <span class="font-bold text-teal-600 dark:text-teal-300">{{ $bs['day_label'] }} {{ $bs['hour_label'] }}</span>
                        <span>({{ number_format($bs['read_rate'], 1) }}%)</span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold
                            {{ $bs['confidence'] === 'High'   ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' :
                               ($bs['confidence'] === 'Medium' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' :
                               'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200') }}">
                            {{ __($bs['confidence']) }} {{ __('confidence') }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Insights --}}
        @if(count($insights) > 0)
            <div class="flex flex-col gap-3">
                @foreach($insights as $insight)
                    <div class="p-4 rounded-xl border flex items-start gap-4
                        {{ $insight['type'] === 'warning'
                            ? 'bg-amber-50 border-amber-100 dark:bg-amber-900/20 dark:border-amber-800'
                            : 'bg-slate-50 border-slate-100 dark:bg-slate-800/50 dark:border-slate-800' }}">

                        <div class="p-2 rounded-lg shrink-0
                            {{ $insight['type'] === 'warning'
                                ? 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200'
                                : 'bg-white text-wa-teal shadow-sm dark:bg-slate-800 dark:text-wa-teal' }}">
                            @if($insight['type'] === 'warning')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <h4 class="text-[10px] font-black uppercase tracking-widest mb-1
                                {{ $insight['type'] === 'warning' ? 'text-amber-700 dark:text-amber-300' : 'text-slate-900 dark:text-white' }}">
                                {{ $insight['type'] === 'warning' ? 'Confidence Notice' : 'Timing Insight' }}
                            </h4>
                            <p class="text-xs font-medium leading-relaxed
                                {{ $insight['type'] === 'warning' ? 'text-amber-600 dark:text-amber-200' : 'text-slate-500 dark:text-slate-400' }}">
                                {!! $insight['message'] !!}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    @endif

</div>

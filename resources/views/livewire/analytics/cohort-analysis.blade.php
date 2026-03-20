<div class="space-y-8 animate-in fade-in duration-500">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-violet-100 text-violet-600 rounded-lg dark:bg-violet-500/10 dark:text-violet-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    {{ __('Monthly') }} <span class="text-violet-600 dark:text-violet-400">{{ __('Groups') }}</span>
                </h1>
            </div>
            <p class="text-slate-500 font-medium">{{ __('See how people who joined each month buy over time.') }}</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('analytics') }}"
                class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 font-bold text-xs uppercase tracking-widest rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ __('Analytics') }}
            </a>
            <button wire:click="load"
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
    <div class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-800 overflow-x-auto pb-0">
        <a href="{{ route('analytics') }}"
            class="shrink-0 px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-t-xl border border-transparent transition-all text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
            {{ __('Overview') }}
        </a>
        <a href="{{ route('analytics.events') }}"
            class="shrink-0 px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-t-xl border border-transparent transition-all text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
            {{ __('Events') }}
        </a>
        <a href="{{ route('analytics.explorer') }}"
            class="shrink-0 px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-t-xl border border-transparent transition-all text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
            {{ __('Explorer') }}
        </a>
        <a href="{{ route('analytics.templates') }}"
            class="shrink-0 px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-t-xl border border-transparent transition-all text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
            {{ __('Template Heatmap') }}
        </a>
        <span class="shrink-0 px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-t-xl bg-white dark:bg-slate-900 border border-b-white dark:border-slate-700 dark:border-b-slate-900 -mb-px text-violet-600 dark:text-violet-400">
            {{ __('Cohort Analysis') }}
        </span>
    </div>

    {{-- Controls --}}
    <div class="flex flex-col sm:flex-row gap-4">
        {{-- Metric selector --}}
        <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl">
            <button wire:click="$set('metric','orders')"
                class="px-5 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all
                    {{ $metric === 'orders' ? 'bg-white dark:bg-slate-900 text-violet-600 dark:text-violet-400 shadow' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                {{ __('Orders') }}
            </button>
            <button wire:click="$set('metric','conversations')"
                class="px-5 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all
                    {{ $metric === 'conversations' ? 'bg-white dark:bg-slate-900 text-violet-600 dark:text-violet-400 shadow' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                {{ __('Re-engagement') }}
            </button>
        </div>

        {{-- Time window --}}
        <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl">
            @foreach([6, 12, 18] as $m)
                <button wire:click="$set('months', {{ $m }})"
                    class="px-5 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all
                        {{ $months === $m ? 'bg-white dark:bg-slate-900 text-slate-800 dark:text-white shadow' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                    {{ $m }}M
                </button>
            @endforeach
        </div>
    </div>

    {{-- Loading overlay --}}
    <div wire:loading class="text-xs text-slate-400 font-semibold flex items-center gap-2">
        <svg class="animate-spin w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        {{ __('Calculating groups...') }}
    </div>

    @if(empty($cohorts))
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="p-4 bg-slate-100 dark:bg-slate-800 rounded-2xl mb-4">
                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <h3 class="text-base font-black text-slate-700 dark:text-slate-200">{{ __('No group data yet') }}</h3>
            <p class="text-sm text-slate-400 mt-1 max-w-sm">
                {{ __('Contacts need :metric to appear here.', ['metric' => $metric === 'orders' ? __('paid orders') : __('follow-up conversations')]) }}
                {{ __('Data covers contacts added in the last :months months.', ['months' => $months]) }}
            </p>
        </div>

    @else

        {{-- KPI Summary Cards --}}
        @if(!empty($summary))
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-5 shadow-sm">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ __('Months Tracked') }}</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $summary['total_cohorts'] }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ __('months of data') }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-5 shadow-sm">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ __('People Checked') }}</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($summary['total_contacts']) }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ __('last :months months', ['months' => $months]) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-5 shadow-sm">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ __('Avg 30-Day Success') }}</p>
                <p class="text-2xl font-black text-violet-600 dark:text-violet-400">{{ number_format($summary['avg_30d'], 1) }}%</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ $metric === 'orders' ? __('order conversion') : __('re-engagement') }}</p>
            </div>
            @if(!empty($summary['best']))
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-5 shadow-sm">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ __('Best Month (30d)') }}</p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($summary['best']['windows'][30]['rate'], 1) }}%</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ $summary['best']['cohort_label'] }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Trend Insight --}}
        @if(!empty($trend))
        <div class="p-4 rounded-xl border flex items-start gap-4
            {{ $trend['direction'] === 'up'
                ? 'bg-emerald-50 border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-800'
                : 'bg-amber-50 border-amber-100 dark:bg-amber-900/20 dark:border-amber-800' }}">

            <div class="p-2 rounded-lg shrink-0
                {{ $trend['direction'] === 'up'
                    ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-800 dark:text-emerald-200'
                    : 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200' }}">
                @if($trend['direction'] === 'up')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                @endif
            </div>

            <div class="flex-1">
                <h4 class="text-[10px] font-black uppercase tracking-widest mb-1
                    {{ $trend['direction'] === 'up' ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                    {{ $trend['label'] }}
                </h4>
                <p class="text-xs font-medium leading-relaxed
                    {{ $trend['direction'] === 'up' ? 'text-emerald-600 dark:text-emerald-200' : 'text-amber-600 dark:text-amber-200' }}">
                    {{ $trend['prev_label'] }} {{ __('cohort') }}: <strong>{{ number_format($trend['prev_rate'], 1) }}%</strong>
                    {{ $metric === 'orders' ? __('order rate') : __('re-engagement rate') }}
                    →
                    {{ $trend['last_label'] }} {{ __('cohort') }}: <strong>{{ number_format($trend['last_rate'], 1) }}%</strong>
                    <span class="ml-1 font-bold">({{ $trend['diff'] >= 0 ? '+' : '' }}{{ number_format($trend['diff'], 1) }} pp)</span>
                </p>
            </div>
        </div>
        @endif

        {{-- Cohort Table --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">

            {{-- Table Header --}}
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-0.5">{{ __('Monthly Success Table') }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $metric === 'orders'
                            ? __('% of people who joined each month who placed a paid order within 30 / 60 / 90 days.')
                            : __('% of people who joined each month who started a follow-up conversation within 30 / 60 / 90 days.') }}
                    </p>
                </div>
                <div class="hidden sm:flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                    <span>{{ __('Lower') }}</span>
                    <div class="h-2 w-20 rounded-full bg-gradient-to-r from-slate-200 to-violet-500/70"></div>
                    <span>{{ __('Higher') }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <th class="px-6 py-3 text-[9px] font-black uppercase tracking-widest text-slate-400 w-40">{{ __('Group/Month') }}</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-slate-400 text-right">{{ __('Contacts') }}</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-slate-400 text-center w-40">{{ __('30 Days') }}</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-slate-400 text-center w-40">{{ __('60 Days') }}</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-slate-400 text-center w-40">{{ __('90 Days') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cohorts as $cohort)
                        <tr class="border-b border-slate-50 dark:border-slate-800/50 hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-3">
                                <span class="text-xs font-black text-slate-800 dark:text-slate-200">{{ $cohort['cohort_label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ number_format($cohort['cohort_size']) }}</span>
                            </td>

                            @foreach([30, 60, 90] as $w)
                                @php
                                    $cell   = $cohort['windows'][$w];
                                    $alpha  = 0.07 + ($cell['intensity'] / 100) * 0.75;
                                    $bg     = 'background-color: rgba(124, 58, 237, ' . number_format($alpha, 2, '.', '') . ');';
                                    $isZero = $cell['rate'] === 0.0;
                                @endphp
                                <td class="px-4 py-3 text-center">
                                    @if($isZero)
                                        <span class="inline-block w-full rounded-lg py-1.5 text-xs font-bold text-slate-300 dark:text-slate-600 bg-slate-50 dark:bg-slate-800/50">
                                            —
                                        </span>
                                    @else
                                        <span class="inline-block w-full rounded-lg py-1.5 text-xs font-black"
                                            style="{{ $bg }} color: {{ $cell['intensity'] > 55 ? '#ffffff' : 'inherit' }};"
                                            title="{{ number_format($cell['converted']) }} of {{ number_format($cohort['cohort_size']) }} contacts">
                                            {{ number_format($cell['rate'], 1) }}%
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-3 border-t border-slate-50 dark:border-slate-800 text-[10px] text-slate-400">
                {{ __('Hover a cell to see exact converted / total counts. Months with no events show —.') }}
            </div>
        </div>

    @endif

</div>

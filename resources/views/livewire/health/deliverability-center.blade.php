@php
    $ratingStyles = [
        'GREEN' => ['bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'Healthy'],
        'YELLOW' => ['bg-amber-50', 'text-amber-700', 'border-amber-200', 'At risk'],
        'RED' => ['bg-rose-50', 'text-rose-700', 'border-rose-200', 'Critical'],
    ];
    $rating = strtoupper($latest->quality_rating ?? 'UNKNOWN');
    [$rBg, $rText, $rBorder, $rLabel] = $ratingStyles[$rating] ?? ['bg-slate-50', 'text-slate-600', 'border-slate-200', 'Not yet measured'];

    $severityStyles = [
        'emergency' => ['bg-rose-50', 'border-rose-200', 'text-rose-700'],
        'critical' => ['bg-rose-50', 'border-rose-200', 'text-rose-700'],
        'warning' => ['bg-amber-50', 'border-amber-200', 'text-amber-700'],
        'info' => ['bg-sky-50', 'border-sky-200', 'text-sky-700'],
    ];
@endphp

<div class="py-10 px-6 max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Deliverability</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">
                How Meta is rating your number, and where it's heading.
            </p>
        </div>

        <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl">
            @foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days'] as $value => $label)
                <button wire:click="setRange({{ $value }})"
                    class="px-4 py-2 text-[11px] font-black uppercase tracking-widest rounded-xl transition-all
                        {{ $days === $value ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    @if (! $latest)
        {{-- Nothing recorded yet: be explicit about why rather than showing empty charts. --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-12 text-center">
            <h2 class="text-lg font-black text-slate-900 dark:text-white">No health history yet</h2>
            <p class="text-sm text-slate-500 font-medium mt-2 max-w-md mx-auto">
                Readings are recorded every 30 minutes once WhatsApp is connected. Your first data point should
                appear within the hour.
            </p>
            <a href="{{ route('teams.whatsapp_config') }}"
                class="inline-block mt-6 px-6 py-3 bg-slate-900 dark:bg-white dark:text-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl">
                Check WhatsApp setup
            </a>
        </div>
    @endif

    @if ($latest)

        {{-- Top row: current state --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">

            <div class="{{ $rBg }} border {{ $rBorder }} rounded-[2rem] p-6">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] {{ $rText }} opacity-70">Quality rating</p>
                <p class="text-3xl font-black {{ $rText }} mt-2">{{ $rating }}</p>
                <p class="text-[11px] font-bold {{ $rText }} opacity-70 mt-1">{{ $rLabel }}</p>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-6">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Health score</p>
                <div class="flex items-baseline gap-2 mt-2">
                    <p class="text-3xl font-black text-slate-900 dark:text-white">{{ $latest->health_score }}</p>
                    @if ($delta !== null && $delta !== 0)
                        <span class="text-[11px] font-black {{ $delta > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $delta > 0 ? '+' : '' }}{{ $delta }}
                        </span>
                    @endif
                </div>
                <p class="text-[11px] font-bold text-slate-400 mt-1">
                    @if ($delta === null)
                        Not enough history
                    @elseif ($delta > 0)
                        Improving over {{ $days }} days
                    @elseif ($delta < 0)
                        Declining over {{ $days }} days
                    @else
                        Flat over {{ $days }} days
                    @endif
                </p>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-6">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Messaging tier</p>
                <p class="text-3xl font-black text-slate-900 dark:text-white mt-2">
                    {{ str_replace('TIER_', '', $latest->messaging_tier ?? '—') }}
                </p>
                <p class="text-[11px] font-bold text-slate-400 mt-1">
                    {{ $latest->daily_limit ? number_format($latest->daily_limit).' / day' : 'Limit unknown' }}
                </p>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-6">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Daily usage</p>
                <p class="text-3xl font-black text-slate-900 dark:text-white mt-2">{{ round($latest->usage_percent ?? 0) }}%</p>
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden mt-3">
                    <div class="h-full rounded-full transition-all {{ ($latest->usage_percent ?? 0) > 80 ? 'bg-rose-500' : 'bg-wa-teal' }}"
                        style="width: {{ min(100, $latest->usage_percent ?? 0) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Forecast --}}
        @php
            $fcStyles = [
                'declining' => ['bg-rose-50', 'border-rose-200', 'text-rose-700'],
                'improving' => ['bg-emerald-50', 'border-emerald-200', 'text-emerald-700'],
                'stable' => ['bg-slate-50', 'border-slate-200', 'text-slate-700'],
            ];
        @endphp

        @if (! $forecast)
            <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-6 mb-8 flex items-start gap-4">
                <div class="h-9 w-9 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center text-slate-400 flex-shrink-0 text-sm font-black">?</div>
                <div>
                    <p class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">Forecast unavailable</p>
                    <p class="text-[11px] font-bold text-slate-400 mt-1">
                        Needs at least {{ $minForecastDays }} days of readings — currently {{ count($trend) }}.
                        We'd rather show nothing than guess about your number.
                    </p>
                </div>
            </div>
        @else
            @php [$fBg, $fBorder, $fText] = $fcStyles[$forecast['direction']] ?? $fcStyles['stable']; @endphp
            <div class="{{ $fBg }} border {{ $fBorder }} rounded-[2rem] p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] {{ $fText }} opacity-70">Forecast</p>
                        <p class="text-lg font-black {{ $fText }} mt-1">
                            @if ($forecast['direction'] === 'declining')
                                Declining {{ abs($forecast['slope']) }} points/day
                            @elseif ($forecast['direction'] === 'improving')
                                Improving {{ $forecast['slope'] }} points/day
                            @else
                                Holding steady
                            @endif
                        </p>

                        @if ($forecast['days_to_critical'])
                            <p class="text-xs font-bold {{ $fText }} mt-1">
                                On this trend you reach critical (score {{ 50 }}) in about
                                <span class="underline">{{ $forecast['days_to_critical'] }} days</span>.
                            </p>
                        @endif
                    </div>

                    {{-- Always show what the projection is based on: a low-confidence
                         line on 7 noisy days should not read like a promise. --}}
                    <div class="text-left md:text-right flex-shrink-0">
                        <p class="text-[10px] font-black uppercase tracking-widest {{ $fText }} opacity-60">
                            {{ $forecast['confidence'] }} confidence
                        </p>
                        <p class="text-[10px] font-bold {{ $fText }} opacity-50 mt-0.5">
                            {{ $forecast['sample_days'] }} days · fit {{ number_format($forecast['r_squared'] * 100) }}%
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Health score trend: plain SVG polyline, no charting dependency --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Health score trend</h2>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Daily average</span>
            </div>

            @if (count($trend) < 2)
                <p class="text-sm text-slate-400 font-medium py-8 text-center">
                    Need at least two days of readings to draw a trend.
                </p>
            @else
                @php
                    $w = 1000; $h = 200; $n = count($trend);
                    $pt = function ($i, $score) use ($w, $h, $n) {
                        $x = $n === 1 ? 0 : ($i / ($n - 1)) * $w;
                        $y = $h - (max(0, min(100, $score)) / 100) * $h;
                        return round($x, 1).','.round($y, 1);
                    };
                    $line = collect($trend)->map(fn ($d, $i) => $pt($i, $d['score']))->implode(' ');
                    $area = "0,{$h} ".$line." {$w},{$h}";
                @endphp

                <div class="overflow-x-auto">
                    <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" class="w-full h-48" role="img"
                        aria-label="Health score over the last {{ $days }} days">
                        {{-- 80 / 50 reference lines: the healthy and critical thresholds --}}
                        <line x1="0" y1="{{ $h * 0.2 }}" x2="{{ $w }}" y2="{{ $h * 0.2 }}" stroke="currentColor" class="text-emerald-200" stroke-width="1" stroke-dasharray="4 6" />
                        <line x1="0" y1="{{ $h * 0.5 }}" x2="{{ $w }}" y2="{{ $h * 0.5 }}" stroke="currentColor" class="text-rose-200" stroke-width="1" stroke-dasharray="4 6" />

                        <polygon points="{{ $area }}" class="text-wa-teal" fill="currentColor" fill-opacity="0.08" />
                        <polyline points="{{ $line }}" fill="none" stroke="currentColor" class="text-wa-teal" stroke-width="2.5"
                            stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                    </svg>
                </div>

                <div class="flex justify-between mt-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <span>{{ \Illuminate\Support\Carbon::parse($trend[0]['day'])->format('d M') }}</span>
                    <span>{{ \Illuminate\Support\Carbon::parse($trend[count($trend) - 1]['day'])->format('d M') }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- What was sending before the last drop --}}
    @if ($lastDrop && count($suspects))
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-8 mb-8">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">
                        Sending before your last drop
                    </h2>
                    <p class="text-[11px] font-bold text-slate-400 mt-1">
                        {{ $attributionWindow }} hours before
                        {{ strtoupper($lastDrop->previous_rating ?? 'UNKNOWN') }} → {{ strtoupper($lastDrop->new_rating) }}
                        on {{ $lastDrop->created_at->format('d M Y, H:i') }}
                    </p>
                </div>
            </div>

            {{-- The caveat sits above the data, not under it. Ranking by volume
                 means your busiest template always tops this list whether or not
                 it had anything to do with the drop. --}}
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
                <div class="h-6 w-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-[11px] font-black flex-shrink-0">!</div>
                <p class="text-[11px] font-bold text-amber-800 leading-relaxed">
                    Meta doesn't tell us which template caused a rating change. This is what was sending
                    beforehand, ranked by volume — <span class="underline">correlation, not proof</span>.
                    Your highest-volume template will appear at the top either way. Use it to decide what to
                    investigate, not what to blame.
                </p>
            </div>

            <div class="space-y-3">
                @foreach ($suspects as $suspect)
                    <div class="flex items-center gap-4">
                        <div class="w-48 flex-shrink-0 min-w-0">
                            <p class="text-xs font-black text-slate-900 dark:text-white truncate" title="{{ $suspect['template'] }}">
                                {{ $suspect['template'] }}
                            </p>
                        </div>
                        <div class="flex-1 bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                            <div class="h-full bg-slate-400 dark:bg-slate-600 rounded-full" style="width: {{ $suspect['share'] }}%"></div>
                        </div>
                        <div class="w-32 flex-shrink-0 text-right">
                            <span class="text-xs font-black text-slate-900 dark:text-white">{{ number_format($suspect['sent']) }}</span>
                            <span class="text-[10px] font-bold text-slate-400 ml-1">{{ $suspect['share'] }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Rating history and alerts render regardless of snapshot state: a team can
         have a RED alert before its first snapshot lands, and hiding it would be
         the exact failure this page exists to prevent. --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            {{-- Rating change timeline --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-8">
                <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest mb-6">Rating changes</h2>

                @forelse ($ratingChanges as $change)
                    @php
                        $isDrop = in_array(strtoupper($change->new_rating), ['RED', 'YELLOW'], true)
                            && strtoupper($change->new_rating) !== strtoupper($change->previous_rating ?? '');
                    @endphp
                    <div class="flex items-start gap-4 pb-5 mb-5 border-b border-slate-100 dark:border-slate-800 last:border-0 last:mb-0 last:pb-0">
                        <div class="h-8 w-8 rounded-xl flex-shrink-0 flex items-center justify-center text-[11px] font-black
                            {{ $isDrop ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' }}">
                            {{ $isDrop ? '↓' : '↑' }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-black text-slate-900 dark:text-white">
                                {{ strtoupper($change->previous_rating ?? 'UNKNOWN') }} → {{ strtoupper($change->new_rating) }}
                            </p>
                            <p class="text-[11px] font-bold text-slate-400 mt-1">
                                {{ $change->created_at->diffForHumans() }} · {{ $change->created_at->format('d M Y, H:i') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 font-medium py-6 text-center">
                        No rating changes in the last {{ $days }} days. Stability is good news.
                    </p>
                @endforelse
            </div>

            {{-- Open alerts --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-8">
                <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest mb-6">
                    Open alerts
                    @if ($alerts->isNotEmpty())
                        <span class="ml-2 px-2 py-0.5 bg-rose-50 text-rose-600 rounded-lg text-[10px]">{{ $alerts->count() }}</span>
                    @endif
                </h2>

                @forelse ($alerts as $alert)
                    @php [$aBg, $aBorder, $aText] = $severityStyles[$alert->severity] ?? $severityStyles['info']; @endphp
                    <div class="{{ $aBg }} border {{ $aBorder }} rounded-2xl p-4 mb-3 last:mb-0">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-widest {{ $aText }} opacity-70">
                                    {{ $alert->dimension }} · {{ str_replace('_', ' ', $alert->alert_type) }}
                                </p>
                                <p class="text-xs font-bold {{ $aText }} mt-1">{{ $alert->message }}</p>
                                <p class="text-[10px] font-bold {{ $aText }} opacity-60 mt-1">{{ $alert->created_at->diffForHumans() }}</p>
                            </div>
                            <button wire:click="acknowledge({{ $alert->id }})" wire:loading.attr="disabled"
                                class="flex-shrink-0 px-3 py-2 bg-white/70 hover:bg-white {{ $aText }} text-[10px] font-black uppercase tracking-widest rounded-xl transition-colors">
                                Got it
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 font-medium py-6 text-center">
                        Nothing needs your attention right now.
                    </p>
                @endforelse
            </div>
    </div>
</div>

@props(['contact'])

@php
    $score = $contact->engagement_score ?? 0;
    $lifecycle = $contact->lifecycle_state ?? 'new';

    // Color coding based on score
    if ($score >= 75) {
        $scoreColor = 'text-emerald-500';
        $scoreBg = 'bg-emerald-500/10';
        $scoreRing = 'stroke-emerald-500';
    } elseif ($score >= 50) {
        $scoreColor = 'text-wa-teal';
        $scoreBg = 'bg-wa-teal/10';
        $scoreRing = 'stroke-wa-teal';
    } elseif ($score >= 25) {
        $scoreColor = 'text-amber-500';
        $scoreBg = 'bg-amber-500/10';
        $scoreRing = 'stroke-amber-500';
    } else {
        $scoreColor = 'text-rose-500';
        $scoreBg = 'bg-rose-500/10';
        $scoreRing = 'stroke-rose-500';
    }

    // Lifecycle badge colors
    $lifecycleColors = [
        'new' => ['bg' => 'bg-blue-500/10', 'text' => 'text-blue-500', 'label' => 'NEW'],
        'active' => ['bg' => 'bg-wa-teal/10', 'text' => 'text-wa-teal', 'label' => 'ACTIVE'],
        'engaged' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-500', 'label' => 'ENGAGED'],
        'dormant' => ['bg' => 'bg-amber-500/10', 'text' => 'text-amber-500', 'label' => 'DORMANT'],
        'churned' => ['bg' => 'bg-rose-500/10', 'text' => 'text-rose-500', 'label' => 'CHURNED'],
    ];

    $lifecycleStyle = $lifecycleColors[$lifecycle] ?? $lifecycleColors['new'];

    $daysSinceLastMessage = $contact->last_interaction_at
        ? (int) abs(now()->diffInDays($contact->last_interaction_at))
        : null;
@endphp

<div class="flex items-center gap-3">
    {{-- Engagement Score Circle --}}
    <div class="relative">
        <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
            <circle cx="18" cy="18" r="16" fill="none" class="stroke-slate-200 dark:stroke-slate-700" stroke-width="3">
            </circle>
            <circle cx="18" cy="18" r="16" fill="none" class="{{ $scoreRing }}" stroke-width="3"
                stroke-dasharray="{{ $score }}, 100" stroke-linecap="round"></circle>
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
            <span class="{{ $scoreColor }} text-xs font-black">{{ $score }}</span>
        </div>
    </div>

    {{-- Health Indicators --}}
    <div class="flex flex-col gap-1">
        <div class="flex items-center gap-2">
            <span
                class="px-2 py-0.5 {{ $lifecycleStyle['bg'] }} {{ $lifecycleStyle['text'] }} text-[9px] font-black uppercase tracking-wider rounded">
                {{ $lifecycleStyle['label'] }}
            </span>

            @if($daysSinceLastMessage !== null)
                @if($daysSinceLastMessage === 0)
                    <span class="text-[9px] text-emerald-500 font-bold flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        Active Today
                    </span>
                @elseif($daysSinceLastMessage <= 7)
                    <span class="text-[9px] text-slate-500 dark:text-slate-400 font-medium">
                        {{ $daysSinceLastMessage }}d ago
                    </span>
                @elseif($daysSinceLastMessage <= 30)
                    <span class="text-[9px] text-amber-500 font-bold flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ $daysSinceLastMessage }}d ago
                    </span>
                @else
                    <span class="text-[9px] text-rose-500 font-bold flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ $daysSinceLastMessage }}d ago
                    </span>
                @endif
            @endif
        </div>

        {{-- Additional Metrics --}}
        <div class="flex items-center gap-2 text-[9px] text-slate-400 font-medium">
            @if($contact->has_pending_reply)
                <span class="flex items-center gap-1 text-wa-teal">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                    Pending
                </span>
            @endif

            @if($contact->is_within_24h_window)
                <span class="flex items-center gap-1 text-emerald-500">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                            clip-rule="evenodd" />
                    </svg>
                    24h Window
                </span>
            @endif
        </div>
    </div>
</div>
@php
    $team = auth()->user()->currentTeam;
    $inGrace = $team->isInGracePeriod();
@endphp

@if($inGrace)
    <div class="bg-amber-600 text-white py-2 px-4 text-center text-xs font-black uppercase tracking-widest animate-pulse">
        ⏳ Workspace suspended in {{ now()->diffInDays($team->subscription_grace_ends_at) }} days. Please resolve your
        subscription status.
    </div>
@endif
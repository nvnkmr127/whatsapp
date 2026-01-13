@php
    $billing = new \App\Services\BillingService();
    $percentage = $billing->getUsagePercentage(auth()->user()->currentTeam);
@endphp

@if($percentage >= 100)
    <div class="bg-red-600 text-white p-3 text-center font-bold">
        🛑 Plan Limit Reached! Your messaging is paused. <a href="#" class="underline ml-2">Upgrade to Pro</a>
    </div>
@elseif($percentage >= 80)
    <div class="bg-yellow-500 text-white p-3 text-center font-bold">
        ⚠️ You have used {{ number_format($percentage) }}% of your monthly limit. <a href="#" class="underline ml-2">Upgrade
            Plan</a>
    </div>
@endif
<x-mail::message>
    # Usage Limit Alert

    Hello,

    This is an automated alert from **{{ config('app.name') }}** regarding your team: **{{ $team->name }}**.

    One of your resource metrics has reached a critical threshold:

    - **Metric:** {{ ucfirst(str_replace('_', ' ', $metric)) }}
    - **Usage:** {{ number_format($percent, 1) }}%
    - **Status:** {{ strtoupper($level) }}

    **Message:**
    {{ $alertMessage }}

    @if($level === 'danger')
        Your service may be restricted until you upgrade your plan.
    @else
        We recommend reviewing your usage or upgrading soon to avoid service disruption.
    @endif

    <x-mail::button :url="route('billing')">
        View Billing Dashboard
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
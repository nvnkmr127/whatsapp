<x-mail::message>
# Daily Activity Report
**Date:** {{ $date }}
**Team:** {{ $team->name }}

Here is a summary of your activity for the last 24 hours:

<x-mail::panel>
### Messaging Stats
- **Sent:** {{ $stats['sent'] }} messages
- **Delivered:** {{ $stats['delivered'] }} ({{ $stats['delivery_rate'] }}%)
- **Read:** {{ $stats['read'] }} ({{ $stats['read_rate'] }}%)
- **Inbound:** {{ $stats['inbound'] }} messages received
</x-mail::panel>

<x-mail::panel>
### AI & Automations
- **AI Interactions:** {{ $stats['ai_interactions'] }}
- **Automations Triggered:** {{ $stats['automation_runs'] }}
</x-mail::panel>

<x-mail::panel>
### Voice Calls
- **Total Calls:** {{ $stats['calls']['count'] }}
- **Minutes Used:** {{ $stats['calls']['minutes'] }} min
</x-mail::panel>

<x-mail::panel>
### Conversation Stats
- **New Conversations:** {{ $stats['new_conversations'] }}
- **Active Sessions:** {{ $stats['active_conversations'] }}
</x-mail::panel>

@if(!empty($stats['top_contacts']))
<x-mail::panel>
### Top 3 Active Contacts
@foreach($stats['top_contacts'] as $contact)
- **{{ $contact['name'] }}**: {{ $contact['count'] }} messages
@endforeach
</x-mail::panel>
@endif

@if(($stats['billing']['balance'] ?? 0) < 5)
<x-mail::panel>
### ⚠️ Low Wallet Balance
Your current balance is **${{ number_format($stats['billing']['balance'], 2) }}**. Please top up soon to ensure uninterrupted service.
</x-mail::panel>
@endif

<x-mail::button :url="config('app.url').'/dashboard'">
Open Professional Dashboard
</x-mail::button>

Thanks,  
{{ config('app.name') }}
</x-mail::message>

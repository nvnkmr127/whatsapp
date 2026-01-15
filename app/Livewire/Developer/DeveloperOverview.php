<?php

namespace App\Livewire\Developer;

use Livewire\Component;

class DeveloperOverview extends Component
{
    public function render()
    {
        $team = auth()->user()->currentTeam;
        $user = auth()->user();

        $stats = [
            'api_tokens' => $user->tokens()->count(),
            'webhook_subscriptions' => \App\Models\WebhookSubscription::where('team_id', $team->id)->count(),
            'webhook_sources' => \App\Models\WebhookSource::where('team_id', $team->id)->count(),
            'recent_deliveries' => \App\Models\WebhookDelivery::whereHas('subscription', function ($q) use ($team) {
                $q->where('team_id', $team->id);
            })->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('livewire.developer.developer-overview', compact('stats'));
    }
}

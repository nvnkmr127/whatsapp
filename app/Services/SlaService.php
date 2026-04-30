<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\SlaPolicy;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class SlaService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Assign SLA policy to a new/reopened conversation and set due timestamps
    // ─────────────────────────────────────────────────────────────────────────
    public function assignPolicy(Conversation $conversation): void
    {
        $policy = SlaPolicy::where('team_id', $conversation->team_id)
            ->where('is_default', true)
            ->first();

        if (! $policy) return;

        $now = now();
        $conversation->update([
            'sla_policy_id'              => $policy->id,
            'sla_first_response_due_at'  => $policy->first_response_minutes > 0
                ? $now->copy()->addMinutes($policy->first_response_minutes)
                : null,
            'sla_resolution_due_at'      => $policy->resolution_minutes > 0
                ? $now->copy()->addMinutes($policy->resolution_minutes)
                : null,
            'sla_status'                 => 'on_track',
        ]);

        Log::debug("[SLA] Policy #{$policy->id} assigned to conversation #{$conversation->id}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mark first response time when an agent sends the first reply
    // ─────────────────────────────────────────────────────────────────────────
    public function recordFirstResponse(Conversation $conversation): void
    {
        if ($conversation->first_response_at) return; // already recorded

        $conversation->update([
            'first_response_at' => now(),
            'sla_status'        => $this->computeStatus($conversation),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Run the breach check (called by the scheduled job / artisan command)
    // ─────────────────────────────────────────────────────────────────────────
    public function checkBreaches(Team $team): array
    {
        $now      = now();
        $breached = [];
        $warning  = [];

        $conversations = Conversation::where('team_id', $team->id)
            ->where('status', 'open')
            ->whereNotNull('sla_policy_id')
            ->whereNotIn('sla_status', ['breached'])
            ->get();

        foreach ($conversations as $conversation) {
            $status = $this->computeStatus($conversation);

            if ($status !== $conversation->sla_status) {
                $conversation->update(['sla_status' => $status]);

                if ($status === 'breached') {
                    $breached[] = $conversation->id;
                    $this->notifyBreach($conversation);
                } elseif ($status === 'breaching_soon') {
                    $warning[] = $conversation->id;
                    $this->notifyWarningSoon($conversation);
                }
            }
        }

        return ['breached' => count($breached), 'warning' => count($warning)];
    }

    public function computeStatus(Conversation $conversation): string
    {
        $now = now();

        // Check first response SLA (only if not yet responded)
        if (! $conversation->first_response_at && $conversation->sla_first_response_due_at) {
            if ($now->gt($conversation->sla_first_response_due_at)) {
                return 'breached';
            }
            if ($now->gt($conversation->sla_first_response_due_at->copy()->subMinutes(15))) {
                return 'breaching_soon';
            }
        }

        // Check resolution SLA
        if ($conversation->sla_resolution_due_at) {
            if ($now->gt($conversation->sla_resolution_due_at)) {
                return 'breached';
            }
            if ($now->gt($conversation->sla_resolution_due_at->copy()->subMinutes(30))) {
                return 'breaching_soon';
            }
        }

        return 'on_track';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Dashboard analytics
    // ─────────────────────────────────────────────────────────────────────────
    public function getTeamStats(Team $team, int $days = 30): array
    {
        $conversations = Conversation::where('team_id', $team->id)
            ->whereNotNull('sla_policy_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $total    = $conversations->count();
        $breached = $conversations->where('sla_status', 'breached')->count();
        $onTrack  = $conversations->where('sla_status', 'on_track')->count();

        // First response time distribution
        $responded = $conversations->whereNotNull('first_response_at');
        $avgFirstResponse = $responded->map(function ($c) {
            return $c->first_response_at->diffInMinutes($c->created_at);
        })->avg();

        return [
            'total'               => $total,
            'breached'            => $breached,
            'breach_rate'         => $total > 0 ? round(($breached / $total) * 100, 1) : 0,
            'on_track'            => $onTrack,
            'avg_first_response'  => $avgFirstResponse ? round($avgFirstResponse) : null,
            'by_status'           => $conversations->groupBy('sla_status')->map->count(),
        ];
    }

    private function notifyBreach(Conversation $conversation): void
    {
        $userToNotify = $conversation->assignee ?? $conversation->team?->owner;
        if (! $userToNotify) return;

        $userToNotify->notify(new \App\Notifications\WhatsAppHealthNotification(
            $conversation->team,
            'sla_breach',
            "🚨 SLA Breached: Conversation #{$conversation->id} with {$conversation->contact?->name} exceeded SLA.",
            ['conversation_id' => $conversation->id]
        ));
    }

    private function notifyWarningSoon(Conversation $conversation): void
    {
        $userToNotify = $conversation->assignee ?? $conversation->team?->owner;
        if (! $userToNotify) return;

        $userToNotify->notify(new \App\Notifications\WhatsAppHealthNotification(
            $conversation->team,
            'sla_warning',
            "⚠️ SLA Warning: Conversation #{$conversation->id} is about to breach SLA in the next 30 minutes.",
            ['conversation_id' => $conversation->id]
        ));
    }
}

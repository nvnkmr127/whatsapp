<?php

namespace App\Services\Health;

use App\Models\Team;
use App\Models\WhatsAppHealthSnapshot;
use App\Services\WhatsAppHealthMonitor;

/**
 * DeliverabilityPreflight
 * ═══════════════════════
 * Answers "will this send actually go out, and should it" at compose time.
 *
 * Distinct from OutboundPreflightService, which covers billing and plan limits.
 * This covers WhatsApp deliverability: blocking health issues, tier capacity and
 * quality rating.
 *
 * Blocking issues come from WhatsAppHealthMonitor::getBlockingIssues() — the
 * exact source BroadcastService gates on at launch — so what the wizard warns
 * about and what actually stops a send cannot drift apart.
 */
class DeliverabilityPreflight
{
    public function __construct(private readonly WhatsAppHealthMonitor $monitor) {}

    /**
     * @return array{
     *     blocking: array<int, string>,
     *     warnings: array<int, string>,
     *     daily_limit: int|null,
     *     remaining: int|null,
     *     quality_rating: string|null
     * }
     */
    public function check(Team $team, int $audienceCount): array
    {
        $blocking = $this->monitor->getBlockingIssues($team);

        // Read the latest snapshot rather than calling Meta: this runs on every
        // wizard render, and the scheduler refreshes it regularly anyway.
        $snapshot = WhatsAppHealthSnapshot::where('team_id', $team->id)
            ->orderByDesc('snapshot_at')
            ->first();

        $dailyLimit = $snapshot?->daily_limit;
        $used = (int) ($snapshot?->current_usage ?? 0);
        $remaining = $dailyLimit !== null ? max(0, $dailyLimit - $used) : null;
        $rating = $snapshot?->quality_rating ? strtoupper($snapshot->quality_rating) : null;

        $warnings = [];

        if ($remaining !== null && $audienceCount > $remaining) {
            $over = $audienceCount - $remaining;
            $warnings[] = sprintf(
                'This campaign targets %s contacts but only %s of today\'s %s message limit remain. About %s message(s) will not send today.',
                number_format($audienceCount),
                number_format($remaining),
                number_format($dailyLimit),
                number_format($over)
            );
        }

        if ($rating === 'RED') {
            $warnings[] = 'Your quality rating is RED. Sending marketing volume now risks Meta restricting your number.';
        } elseif ($rating === 'YELLOW') {
            $warnings[] = 'Your quality rating is YELLOW. Consider a smaller or better-targeted audience until it recovers.';
        }

        return [
            'blocking' => array_values($blocking),
            'warnings' => $warnings,
            'daily_limit' => $dailyLimit,
            'remaining' => $remaining,
            'quality_rating' => $rating,
        ];
    }
}

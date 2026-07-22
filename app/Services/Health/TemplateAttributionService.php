<?php

namespace App\Services\Health;

use App\Models\Message;
use App\Models\QualityRatingHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * TemplateAttributionService
 * ══════════════════════════
 * Answers "what was in flight when the rating fell" — NOT "what caused it".
 *
 * Meta does not tell us which template triggered a rating change, so this is
 * strictly correlational: it lists the templates sent in the window before a
 * drop, ranked by volume. High-volume templates will always appear near the
 * top whether or not they are responsible, and the UI must say so.
 */
class TemplateAttributionService
{
    /** Hours before a drop to consider a send "in flight". */
    public const WINDOW_HOURS = 72;

    /** Templates below this share of window volume are noise. */
    private const MIN_SHARE_PERCENT = 1.0;

    /**
     * The most recent rating drop for a team, or null if there hasn't been one.
     */
    public function latestDrop(int $teamId, int $withinDays): ?QualityRatingHistory
    {
        return QualityRatingHistory::where('team_id', $teamId)
            ->where('created_at', '>=', now()->subDays($withinDays))
            ->whereIn(DB::raw('UPPER(new_rating)'), ['YELLOW', 'RED'])
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Templates sent in the window preceding $dropAt, ranked by volume.
     *
     * @return array<int, array{template: string, sent: int, share: float}>
     */
    public function templatesBefore(int $teamId, Carbon $dropAt, ?int $hours = null): array
    {
        $hours ??= self::WINDOW_HOURS;
        $windowStart = (clone $dropAt)->subHours($hours);

        // json_extract works on both MySQL and SQLite, so the same query serves
        // production and the test suite without a driver branch.
        $rows = Message::query()
            ->where('team_id', $teamId)
            ->where('direction', 'outbound')
            ->where('type', 'template')
            ->whereBetween('created_at', [$windowStart, $dropAt])
            ->selectRaw("json_extract(metadata, '\$.template_name') as template_name")
            ->selectRaw('COUNT(*) as sent')
            ->groupBy('template_name')
            ->orderByDesc('sent')
            ->get();

        $total = (int) $rows->sum('sent');

        if ($total === 0) {
            return [];
        }

        return $rows
            ->filter(fn ($r) => filled($r->template_name))
            ->map(fn ($r) => [
                'template' => trim((string) $r->template_name, '"'),
                'sent' => (int) $r->sent,
                'share' => round(((int) $r->sent / $total) * 100, 1),
            ])
            ->filter(fn ($r) => $r['share'] >= self::MIN_SHARE_PERCENT)
            ->sortByDesc('sent')
            ->values()
            ->all();
    }
}

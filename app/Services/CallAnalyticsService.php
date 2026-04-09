<?php

namespace App\Services;

use App\Models\CallQualityMetric;
use App\Models\WhatsAppCall;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CallAnalyticsService
{
    /**
     * Get aggregated call quality statistics.
     */
    public function getQualityStatistics(array $filters = []): array
    {
        $query = CallQualityMetric::query()
            ->join('whatsapp_calls', 'call_quality_metrics.call_id', '=', 'whatsapp_calls.id');

        if (! empty($filters['start_date'])) {
            $query->where('whatsapp_calls.created_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->where('whatsapp_calls.created_at', '<=', $filters['end_date']);
        }
        if (! empty($filters['team_id'])) {
            $query->where('whatsapp_calls.team_id', $filters['team_id']);
        }

        $totalCalls = $query->count();

        $metrics = [
            'total_calls' => $totalCalls,
            'successful_connections' => $query->clone()->whereNotNull('call_quality_metrics.connection_established_at')->count(),
            'failed_connections' => $query->clone()->whereNull('call_quality_metrics.connection_established_at')
                ->whereNotNull('call_quality_metrics.error_logs')->count(),
            'average_answer_latency' => $query->clone()->avg(DB::raw('TIMESTAMPDIFF(SECOND, sdp_offer_received_at, sdp_answer_sent_at)')),
            'average_connection_latency' => $query->clone()->avg(DB::raw('TIMESTAMPDIFF(SECOND, sdp_answer_sent_at, connection_established_at)')),
            'average_quality_score' => $query->clone()->avg('call_quality_metrics.network_quality_score'),
            'codec_distribution' => $query->clone()->select('selected_codec', DB::raw('count(*) as count'))
                ->groupBy('selected_codec')
                ->pluck('count', 'selected_codec'),
            'quality_distribution' => [
                'excellent' => $query->clone()->where('call_quality_metrics.network_quality_score', '>=', 4)->count(),
                'good' => $query->clone()->whereBetween('call_quality_metrics.network_quality_score', [3, 3.9])->count(),
                'fair' => $query->clone()->whereBetween('call_quality_metrics.network_quality_score', [2, 2.9])->count(),
                'poor' => $query->clone()->where('call_quality_metrics.network_quality_score', '<', 2)->count(),
            ],
        ];

        $metrics['connection_success_rate'] = $totalCalls > 0
            ? round(($metrics['successful_connections'] / $totalCalls) * 100, 2)
            : 0;

        return $metrics;
    }

    /**
     * Get connection success rate over time.
     */
    public function getSuccessRateOverTime(array $filters = []): Collection
    {
        $interval = $filters['interval'] ?? 'day';
        $startDate = ! empty($filters['start_date']) ? Carbon::parse($filters['start_date']) : now()->subDays(30);
        $endDate = ! empty($filters['end_date']) ? Carbon::parse($filters['end_date']) : now();

        $dateFormat = match ($interval) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
        };

        $query = WhatsAppCall::query()
            ->select([
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(CASE WHEN status IN ("answered", "completed") THEN 1 ELSE 0 END) as successful_calls'),
            ])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (! empty($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        return $query->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($row) {
                return [
                    'period' => $row->period,
                    'total_calls' => $row->total_calls,
                    'successful_calls' => $row->successful_calls,
                    'success_rate' => $row->total_calls > 0 ? round(($row->successful_calls / $row->total_calls) * 100, 2) : 0,
                ];
            });
    }

    /**
     * Get dashboard overview metrics.
     */
    public function getDashboardStats(?int $teamId = null): array
    {
        $last24Hours = now()->subDay();

        $recentCalls = WhatsAppCall::query()
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->where('created_at', '>=', $last24Hours);

        return [
            'last_24_hours' => [
                'total_calls' => $recentCalls->count(),
                'answered_calls' => $recentCalls->clone()->where('status', 'answered')->count(),
                'missed_calls' => $recentCalls->clone()->where('status', 'no_answer')->count(),
                'failed_calls' => $recentCalls->clone()->where('status', 'failed')->count(),
                'average_duration' => $recentCalls->clone()->whereNotNull('duration')->avg('duration'),
            ],
            'current_status' => [
                'active_calls' => WhatsAppCall::query()
                    ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
                    ->where('status', 'in_progress')
                    ->count(),
            ],
            'quality_summary' => CallQualityMetric::query()
                ->join('whatsapp_calls', 'call_quality_metrics.call_id', '=', 'whatsapp_calls.id')
                ->when($teamId, fn ($q) => $q->where('whatsapp_calls.team_id', $teamId))
                ->where('whatsapp_calls.created_at', '>=', $last24Hours)
                ->selectRaw('
                    AVG(network_quality_score) as avg_quality,
                    AVG(TIMESTAMPDIFF(SECOND, sdp_offer_received_at, sdp_answer_sent_at)) as avg_answer_latency,
                    AVG(TIMESTAMPDIFF(SECOND, sdp_answer_sent_at, connection_established_at)) as avg_connection_latency
                ')
                ->first(),
        ];
    }
}

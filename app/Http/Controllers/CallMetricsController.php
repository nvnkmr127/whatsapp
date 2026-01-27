<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppCall;
use App\Models\CallQualityMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CallMetricsController extends Controller
{
    /**
     * Get call quality statistics
     */
    public function getQualityStatistics(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $query = CallQualityMetric::query()
            ->join('whatsapp_calls', 'call_quality_metrics.call_id', '=', 'whatsapp_calls.id');

        // Filter by date range
        if ($request->start_date) {
            $query->where('whatsapp_calls.created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('whatsapp_calls.created_at', '<=', $request->end_date);
        }

        // Filter by team
        if ($request->team_id) {
            $query->where('whatsapp_calls.team_id', $request->team_id);
        }

        $metrics = [
            'total_calls' => $query->count(),
            'successful_connections' => $query->whereNotNull('call_quality_metrics.connection_established_at')->count(),
            'failed_connections' => $query->whereNull('call_quality_metrics.connection_established_at')
                ->whereNotNull('call_quality_metrics.error_logs')->count(),
            'average_answer_latency' => $query->avg(DB::raw('TIMESTAMPDIFF(SECOND, sdp_offer_received_at, sdp_answer_sent_at)')),
            'average_connection_latency' => $query->avg(DB::raw('TIMESTAMPDIFF(SECOND, sdp_answer_sent_at, connection_established_at)')),
            'average_quality_score' => $query->avg('call_quality_metrics.network_quality_score'),
            'codec_distribution' => $query->select('selected_codec', DB::raw('count(*) as count'))
                ->groupBy('selected_codec')
                ->pluck('count', 'selected_codec'),
            'quality_distribution' => [
                'excellent' => $query->clone()->where('call_quality_metrics.network_quality_score', '>=', 4)->count(),
                'good' => $query->clone()->whereBetween('call_quality_metrics.network_quality_score', [3, 3.9])->count(),
                'fair' => $query->clone()->whereBetween('call_quality_metrics.network_quality_score', [2, 2.9])->count(),
                'poor' => $query->clone()->where('call_quality_metrics.network_quality_score', '<', 2)->count(),
            ],
        ];

        // Calculate success rate
        $metrics['connection_success_rate'] = $metrics['total_calls'] > 0
            ? round(($metrics['successful_connections'] / $metrics['total_calls']) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Export SDP exchange logs
     */
    public function exportSdpLogs(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'team_id' => 'nullable|exists:teams,id',
            'format' => 'nullable|in:json,csv',
        ]);

        $query = WhatsAppCall::query()
            ->with('qualityMetric')
            ->select([
                'whatsapp_calls.id',
                'whatsapp_calls.call_id',
                'whatsapp_calls.from_number',
                'whatsapp_calls.to_number',
                'whatsapp_calls.status',
                'whatsapp_calls.created_at',
                'whatsapp_calls.metadata',
            ]);

        // Apply filters
        if ($request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('created_at', '<=', $request->end_date);
        }
        if ($request->team_id) {
            $query->where('team_id', $request->team_id);
        }

        $calls = $query->get()->map(function ($call) {
            return [
                'call_id' => $call->call_id,
                'from' => $call->from_number,
                'to' => $call->to_number,
                'status' => $call->status,
                'timestamp' => $call->created_at->toIso8601String(),
                'sdp_offer' => $call->metadata['sdp'] ?? null,
                'sdp_offer_received_at' => $call->qualityMetric?->sdp_offer_received_at?->toIso8601String(),
                'sdp_answer_sent_at' => $call->qualityMetric?->sdp_answer_sent_at?->toIso8601String(),
                'sdp_validation_passed' => $call->qualityMetric?->sdp_validation_passed ?? null,
                'sdp_validation_errors' => $call->qualityMetric?->sdp_validation_errors ?? null,
                'selected_codec' => $call->qualityMetric?->selected_codec,
                'ice_candidates_count' => $call->qualityMetric?->ice_candidates_count,
            ];
        });

        $format = $request->format ?? 'json';

        if ($format === 'csv') {
            $filename = 'sdp_logs_' . now()->format('Y-m-d_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($calls) {
                $file = fopen('php://output', 'w');

                // Add headers
                if ($calls->isNotEmpty()) {
                    fputcsv($file, array_keys($calls->first()));
                }

                // Add data
                foreach ($calls as $call) {
                    fputcsv($file, array_map(function ($value) {
                        return is_array($value) ? json_encode($value) : $value;
                    }, $call));
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return response()->json([
            'success' => true,
            'data' => $calls,
            'count' => $calls->count(),
        ]);
    }

    /**
     * Get failed call attempts
     */
    public function getFailedCalls(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'team_id' => 'nullable|exists:teams,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = WhatsAppCall::query()
            ->with('qualityMetric')
            ->whereIn('status', ['failed', 'rejected', 'busy', 'no_answer']);

        // Apply filters
        if ($request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('created_at', '<=', $request->end_date);
        }
        if ($request->team_id) {
            $query->where('team_id', $request->team_id);
        }

        $limit = $request->limit ?? 50;
        $failedCalls = $query->latest()->limit($limit)->get()->map(function ($call) {
            return [
                'call_id' => $call->call_id,
                'from' => $call->from_number,
                'to' => $call->to_number,
                'status' => $call->status,
                'timestamp' => $call->created_at->toIso8601String(),
                'duration' => $call->duration,
                'error_logs' => $call->qualityMetric?->error_logs ?? [],
                'retry_attempts' => $call->metadata['retry_attempts'] ?? 0,
                'failure_reason' => $call->metadata['failure_reason'] ?? 'Unknown',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $failedCalls,
            'count' => $failedCalls->count(),
        ]);
    }

    /**
     * Get connection success rate over time
     */
    public function getSuccessRate(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'team_id' => 'nullable|exists:teams,id',
            'interval' => 'nullable|in:hour,day,week,month',
        ]);

        $interval = $request->interval ?? 'day';
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->subDays(30);
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now();

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

        if ($request->team_id) {
            $query->where('team_id', $request->team_id);
        }

        $data = $query->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($row) {
                $successRate = $row->total_calls > 0
                    ? round(($row->successful_calls / $row->total_calls) * 100, 2)
                    : 0;

                return [
                    'period' => $row->period,
                    'total_calls' => $row->total_calls,
                    'successful_calls' => $row->successful_calls,
                    'success_rate' => $successRate,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $data,
            'interval' => $interval,
        ]);
    }

    /**
     * Get real-time call metrics dashboard
     */
    public function getDashboard(Request $request)
    {
        $teamId = $request->team_id;

        // Last 24 hours stats
        $last24Hours = now()->subDay();

        $recentCalls = WhatsAppCall::query()
            ->when($teamId, fn($q) => $q->where('team_id', $teamId))
            ->where('created_at', '>=', $last24Hours);

        $dashboard = [
            'last_24_hours' => [
                'total_calls' => $recentCalls->count(),
                'answered_calls' => $recentCalls->clone()->where('status', 'answered')->count(),
                'missed_calls' => $recentCalls->clone()->where('status', 'no_answer')->count(),
                'failed_calls' => $recentCalls->clone()->where('status', 'failed')->count(),
                'average_duration' => $recentCalls->clone()->whereNotNull('duration')->avg('duration'),
            ],
            'current_status' => [
                'active_calls' => WhatsAppCall::query()
                    ->when($teamId, fn($q) => $q->where('team_id', $teamId))
                    ->where('status', 'in_progress')
                    ->count(),
            ],
            'quality_summary' => CallQualityMetric::query()
                ->join('whatsapp_calls', 'call_quality_metrics.call_id', '=', 'whatsapp_calls.id')
                ->when($teamId, fn($q) => $q->where('whatsapp_calls.team_id', $teamId))
                ->where('whatsapp_calls.created_at', '>=', $last24Hours)
                ->selectRaw('
                    AVG(network_quality_score) as avg_quality,
                    AVG(TIMESTAMPDIFF(SECOND, sdp_offer_received_at, sdp_answer_sent_at)) as avg_answer_latency,
                    AVG(TIMESTAMPDIFF(SECOND, sdp_answer_sent_at, connection_established_at)) as avg_connection_latency
                ')
                ->first(),
        ];

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }
}

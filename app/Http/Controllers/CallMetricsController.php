<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppCall;
use Illuminate\Http\Request;

class CallMetricsController extends Controller
{
    protected $analytics;

    public function __construct(\App\Services\CallAnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

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

        $metrics = $this->analytics->getQualityStatistics($request->only(['start_date', 'end_date', 'team_id']));

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
            $filename = 'sdp_logs_'.now()->format('Y-m-d_His').'.csv';
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

        $data = $this->analytics->getSuccessRateOverTime($request->only(['start_date', 'end_date', 'team_id', 'interval']));

        return response()->json([
            'success' => true,
            'data' => $data,
            'interval' => $request->interval ?? 'day',
        ]);
    }

    /**
     * Get real-time call metrics dashboard
     */
    public function getDashboard(Request $request)
    {
        $dashboard = $this->analytics->getDashboardStats($request->team_id);

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }
}

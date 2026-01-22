<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\WhatsAppHealthMonitor;
use Illuminate\Http\Request;

class WhatsAppHealthController extends Controller
{
    public function __construct(
        protected WhatsAppHealthMonitor $healthMonitor
    ) {
    }

    /**
     * Get health status for a team
     */
    public function show(Team $team)
    {
        $health = $this->healthMonitor->checkHealth($team);

        return response()->json([
            'success' => true,
            'data' => [
                'overall_score' => $health['overall_score'],
                'status' => $health['status'],
                'dimensions' => [
                    'token' => $health['token'],
                    'phone' => $health['phone'],
                    'quality' => $health['quality'],
                    'messaging' => $health['messaging'],
                ],
                'alerts' => $health['alerts'],
                'can_send_messages' => $this->healthMonitor->canSendMessages($team),
                'blocking_issues' => $this->healthMonitor->getBlockingIssues($team),
                'checked_at' => $health['checked_at'],
            ],
        ]);
    }

    /**
     * Get health history for a team
     */
    public function history(Team $team, Request $request)
    {
        $days = $request->input('days', 7);

        $snapshots = $team->healthSnapshots()
            ->where('snapshot_at', '>=', now()->subDays($days))
            ->orderBy('snapshot_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $snapshots,
        ]);
    }

    /**
     * Get active alerts for a team
     */
    public function alerts(Team $team)
    {
        $alerts = $this->healthMonitor->getActiveAlerts($team);

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledgeAlert(Team $team, int $alertId)
    {
        $alert = $team->healthAlerts()->findOrFail($alertId);
        $alert->acknowledge(auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged',
        ]);
    }
}

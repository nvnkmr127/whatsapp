<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\WhatsAppHealthMonitor;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;

class WhatsAppHealthController extends Controller
{
    use StandardApiResponses;

    public function __construct(
        protected WhatsAppHealthMonitor $healthMonitor
    ) {}

    /**
     * Get health status for a team
     */
    public function show(Team $team)
    {
        $health = $this->healthMonitor->checkHealth($team);

        return $this->success([
            'overall_score' => $health['overall_score'],
            'status' => $health['status'],
            'dimensions' => [
                'token' => $health['token'],
                'phone' => $health['phone'],
                'quality' => $health['quality'],
                'messaging' => $health['messaging'],
            ],
            'alerts' => $health['alerts'] ?? [],
            'can_send_messages' => $this->healthMonitor->canSendMessages($team),
            'blocking_issues' => $this->healthMonitor->getBlockingIssues($team),
            'checked_at' => $health['checked_at'],
        ], 'WhatsApp health status retrieved.');
    }

    /**
     * Get health history for a team
     */
    public function history(Team $team, Request $request)
    {
        $days = (int) $request->input('days', 7);

        $snapshots = $team->healthSnapshots()
            ->where('snapshot_at', '>=', now()->subDays($days))
            ->orderBy('snapshot_at', 'desc')
            ->get();

        return $this->success($snapshots, 'Health history retrieved.');
    }

    /**
     * Get active alerts for a team
     */
    public function alerts(Team $team)
    {
        $alerts = $this->healthMonitor->getActiveAlerts($team);

        return $this->success($alerts, 'Active health alerts retrieved.');
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledgeAlert(Team $team, int $alertId)
    {
        $alert = $team->healthAlerts()->findOrFail($alertId);
        $alert->acknowledge(auth()->user());

        return $this->success([], 'Alert acknowledged successfully.');
    }
}

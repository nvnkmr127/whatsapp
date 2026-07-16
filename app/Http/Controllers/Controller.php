<?php

namespace App\Http\Controllers;

abstract class Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    /**
     * Write an activity-log entry. Failures are logged but never block the request.
     */
    protected function logAudit(int $teamId, string $action, string $description, array $properties = []): void
    {
        try {
            \App\Models\ActivityLog::create([
                'team_id' => $teamId,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'properties' => array_merge($properties, [
                    'user_agent' => request()->userAgent(),
                ]),
            ]);
        } catch (\Exception $e) {
            \Log::error("Audit log failed [{$action}]: ".$e->getMessage(), ['team_id' => $teamId]);
        }
    }
}

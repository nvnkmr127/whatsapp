<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an activity.
     *
     * @param string $action The action name (e.g., 'campaign.created')
     * @param string|null $description Optional description
     * @param Model|null $subject The model being acted upon
     * @param array|null $properties Additional properties to store (if supported in future)
     */
    public function log(string $action, ?string $description = null, ?Model $subject = null, ?array $properties = []): ActivityLog
    {
        $user = Auth::user();
        $teamId = $user?->currentTeam?->id;

        // Fallback: If no team from user, check subject
        if (!$teamId && $subject && isset($subject->team_id)) {
            $teamId = $subject->team_id;
        }

        return ActivityLog::create([
            'team_id' => $teamId,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'properties' => $properties,
            'ip_address' => Request::ip() ?? '127.0.0.1',
        ]);
    }
}

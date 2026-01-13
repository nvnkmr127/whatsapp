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
        $team = $user?->currentTeam;

        return ActivityLog::create([
            'team_id' => $team?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'ip_address' => Request::ip(),
        ]);
    }
}

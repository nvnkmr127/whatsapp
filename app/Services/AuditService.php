<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Log a security or authentication event.
     * 
     * Handles both specific auth parameters and generic activity parameters.
     */
    public static function log(string $event, $userId = null, $identifier = null, ?string $provider = null, array $metadata = []): void
    {
        $teamId = null;

        // Try to resolve Team ID and User ID from objects if provided
        if ($userId instanceof Model) {
            $teamId = $userId->current_team_id ?? null;
            $userId = $userId->id;
        } elseif (is_string($userId) && !is_numeric($userId)) {
            $metadata['description'] = $userId;
            $userId = null;
        }

        if ($identifier instanceof Model) {
            $teamId = $teamId ?? $identifier->team_id ?? null;
            $identifier = $identifier->email ?? $identifier->phone ?? $identifier->name ?? (string) $identifier;
        }

        // Final fallback for team_id from session
        if (!$teamId && auth()->check()) {
            $teamId = auth()->user()->current_team_id;
        }

        AuditLog::create([
            'user_id' => $userId,
            'team_id' => $teamId,
            'event_type' => $event,
            'identifier' => is_string($identifier) ? $identifier : json_encode($identifier),
            'provider' => $provider,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
        ]);
    }
}

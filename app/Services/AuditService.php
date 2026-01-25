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
        // Polymorphic userId: If a User model is passed
        if ($userId instanceof Model) {
            $model = $userId;
            $userId = $model->id;

            // If identifier wasn't provided, try to get it from the model
            if (is_null($identifier)) {
                $identifier = $model->email ?? $model->phone ?? $model->name ?? null;
            }
        }
        // Polymorphic userId: If a string description is passed (from legacy-style audit calls)
        elseif (is_string($userId) && !is_numeric($userId)) {
            $metadata['description'] = $userId;
            $userId = null;
        }

        // Polymorphic identifier: If a model is passed as third arg
        if ($identifier instanceof Model) {
            $userId = $userId ?? $identifier->id;
            $identifier = $identifier->email ?? $identifier->phone ?? $identifier->name ?? (string) $identifier;
        }

        AuditLog::create([
            'user_id' => $userId,
            'event_type' => $event,
            'identifier' => is_string($identifier) ? $identifier : json_encode($identifier),
            'provider' => $provider,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
        ]);
    }
}

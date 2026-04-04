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
            'identifier' => is_string($identifier) ? self::sanitizeUtf8($identifier) : self::jsonEncodeSafe($identifier),
            'provider' => $provider,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => self::sanitizeMetadata($metadata),
        ]);
    }

    /**
     * Ensure metadata array is safe for JSON encoding.
     */
    private static function sanitizeMetadata(array $metadata): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return self::sanitizeMetadata($value);
            }
            if (is_string($value)) {
                return self::sanitizeUtf8($value);
            }
            return $value;
        }, $metadata);
    }

    /**
     * Sanitize a string to be valid UTF-8.
     */
    private static function sanitizeUtf8(string $string): string
    {
        if (mb_check_encoding($string, 'UTF-8')) {
            return $string;
        }

        // Convert common encodings or fallback to stripping invalid characters
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }

    /**
     * Safe JSON encode that handles malformed characters.
     */
    private static function jsonEncodeSafe($data): string
    {
        return (string) json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}

<?php

namespace App\Services;

use App\Models\Team;
use App\Models\SystemEvent;
use App\Models\WhatsAppSetupAudit;
use Illuminate\Support\Facades\Log;

class WhatsAppEventBridge
{
    /**
     * Log a WABA API interaction
     */
    public static function logInteraction(Team $team, string $event, string $status, array $payload = [], array $metadata = []): void
    {
        try {
            SystemEvent::create([
                'event_id' => (string) \Illuminate\Support\Str::uuid(),
                'team_id' => $team->id,
                'event_type' => 'waba.' . $event,
                'source' => 'whatsapp',
                'category' => in_array($status, ['failed', 'error', 'critical']) ? 'operational' : 'business',
                'payload' => $payload,
                'metadata' => array_merge([
                    'status' => $status,
                    'phone_id' => $team->whatsapp_phone_number_id,
                    'waba_id' => $team->whatsapp_business_account_id,
                ], $metadata),
                'occurred_at' => now(),
                'is_signal' => in_array($status, ['failed', 'error', 'critical']),
            ]);
        } catch (\Exception $e) {
            Log::error("WhatsAppEventBridge: Failed to log interaction: " . $e->getMessage());
        }
    }

    /**
     * Log a configuration change
     */
    public static function auditConfig(Team $team, string $action, string $status, array $changes = [], array $metadata = []): void
    {
        try {
            WhatsAppSetupAudit::create([
                'team_id' => $team->id,
                'user_id' => auth()->id() ?? $team->user_id, // Fallback if CLI
                'action' => $action,
                'status' => $status,
                'changes' => $changes,
                'metadata' => $metadata,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'reference_id' => WhatsAppSetupAudit::generateReferenceId(),
            ]);
        } catch (\Exception $e) {
            Log::error("WhatsAppEventBridge: Failed to log audit: " . $e->getMessage());
        }
    }
}

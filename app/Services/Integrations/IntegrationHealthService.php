<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Enums\IntegrationState;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class IntegrationHealthService
{
    /**
     * Check and update the health status of an integration.
     */
    public function checkHealth(Integration $integration): array
    {
        $issues = [];
        $score = 100;

        // 1. Check Auth Health
        $authHealth = $this->checkAuthHealth($integration);
        if (!$authHealth['valid']) {
            $score = 0;
            $issues[] = $authHealth['error'];
        }

        // 2. Check Sync Staleness
        $syncHealth = $this->checkSyncHealth($integration);
        if ($syncHealth['stale']) {
            $score -= $syncHealth['penalty'];
            $issues[] = $syncHealth['issue'];
        }

        // 3. Check Webhook Pulse (Optional/Future)
        // This would require tracking last webhook reception time in the DB

        $state = $this->determineState($score, $issues);

        // Persist to model (using the health_status JSON if we add it, or updating status)
        $integration->update([
            'status' => $state,
            'error_message' => !empty($issues) ? implode('; ', $issues) : null,
        ]);

        return [
            'state' => $state,
            'score' => $score,
            'issues' => $issues,
            'checked_at' => now(),
        ];
    }

    protected function checkAuthHealth(Integration $integration): array
    {
        // If we recently encountered a 401/403 (logged in error_message or a specific field)
        if (
            str_contains(strtolower($integration->error_message ?? ''), 'unauthorized') ||
            str_contains(strtolower($integration->error_message ?? ''), 'invalid credentials')
        ) {
            return ['valid' => false, 'error' => 'Invalid or expired credentials'];
        }

        return ['valid' => true];
    }

    protected function checkSyncHealth(Integration $integration): array
    {
        if (!$integration->last_synced_at) {
            return ['stale' => true, 'penalty' => 50, 'issue' => 'Never synced'];
        }

        $hoursSinceSync = $integration->last_synced_at->diffInHours();

        if ($hoursSinceSync > 48) {
            return ['stale' => true, 'penalty' => 80, 'issue' => "Extreme sync delay ({$hoursSinceSync} hours)"];
        }

        if ($hoursSinceSync > 24) {
            return ['stale' => true, 'penalty' => 40, 'issue' => "Sync stale ({$hoursSinceSync} hours)"];
        }

        return ['stale' => false, 'penalty' => 0];
    }

    protected function determineState(int $score, array $issues): string
    {
        if (in_array('Invalid or expired credentials', $issues)) {
            return IntegrationState::TOKEN_EXPIRED->value;
        }

        if ($score === 0) {
            return IntegrationState::SUSPENDED->value;
        }

        if ($score < 90) {
            return IntegrationState::DEGRADED->value;
        }

        return IntegrationState::ACTIVE->value;
    }

    /**
     * Mark an integration as broken due to an API error.
     */
    public function reportApiError(Integration $integration, \Exception $e)
    {
        $message = $e->getMessage();
        $isAuthError = str_contains($message, '401') || str_contains($message, '403');

        $state = $isAuthError ? IntegrationState::TOKEN_EXPIRED : IntegrationState::DEGRADED;

        $integration->update([
            'status' => $state->value,
            'error_message' => $message,
        ]);

        Log::warning("Integration {$integration->id} health update: " . $state->name);
    }
}

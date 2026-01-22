<?php

namespace App\Services;

use App\Enums\WhatsAppSetupState;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Team;
use App\Models\WhatsAppSetupAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppSetupStateMachine
{
    /**
     * Transition team to a new setup state
     */
    public function transition(Team $team, WhatsAppSetupState|string $toState, array $metadata = []): void
    {
        // Convert string to enum if needed
        if (is_string($toState)) {
            $toState = WhatsAppSetupState::from($toState);
        }

        $fromState = $this->getCurrentState($team);

        // Validate transition
        if (!$this->canTransition($fromState, $toState)) {
            throw new InvalidStateTransitionException(
                $fromState->value,
                $toState->value,
                'This transition is not allowed'
            );
        }

        DB::transaction(function () use ($team, $fromState, $toState, $metadata) {
            // Update team state
            $updates = [
                'whatsapp_setup_state' => $toState->value,
                'whatsapp_setup_progress' => $metadata,
            ];

            // Set started_at on first transition from NOT_CONFIGURED
            if ($fromState === WhatsAppSetupState::NOT_CONFIGURED && !$team->whatsapp_setup_started_at) {
                $updates['whatsapp_setup_started_at'] = now();
                $updates['whatsapp_setup_in_progress'] = true;
            }

            // Set completed_at when reaching terminal state
            if ($toState->isTerminal()) {
                $updates['whatsapp_setup_completed_at'] = now();
                $updates['whatsapp_setup_in_progress'] = false;
            }

            // Reset retry count on successful progress
            if (!$toState->isError()) {
                $updates['whatsapp_setup_retry_count'] = 0;
            }

            $team->update($updates);

            // Log transition to audit trail
            $this->logTransition($team, $fromState, $toState, $metadata);

            // Trigger state-specific actions
            $this->onStateEnter($team, $toState, $metadata);
        });

        Log::info("WhatsApp setup state transition", [
            'team_id' => $team->id,
            'from' => $fromState->value,
            'to' => $toState->value,
        ]);
    }

    /**
     * Check if transition is valid
     */
    public function canTransition(WhatsAppSetupState $from, WhatsAppSetupState $to): bool
    {
        $allowedTransitions = $from->getAllowedTransitions();
        return in_array($to, $allowedTransitions);
    }

    /**
     * Get current state of team
     */
    public function getCurrentState(Team $team): WhatsAppSetupState
    {
        return WhatsAppSetupState::from($team->whatsapp_setup_state ?? 'NOT_CONFIGURED');
    }

    /**
     * Check if team is in a terminal state
     */
    public function isInTerminalState(Team $team): bool
    {
        return $this->getCurrentState($team)->isTerminal();
    }

    /**
     * Check if team can retry from current state
     */
    public function canRetry(Team $team): bool
    {
        $currentState = $this->getCurrentState($team);
        return $currentState->canRetry() && $team->whatsapp_setup_retry_count < 3;
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(Team $team): void
    {
        $team->increment('whatsapp_setup_retry_count');
    }

    /**
     * Rollback to a safe state
     */
    public function rollback(Team $team, WhatsAppSetupState|string $toState = null): void
    {
        if ($toState === null) {
            $toState = WhatsAppSetupState::NOT_CONFIGURED;
        }

        if (is_string($toState)) {
            $toState = WhatsAppSetupState::from($toState);
        }

        $team->update([
            'whatsapp_setup_state' => $toState->value,
            'whatsapp_setup_progress' => null,
            'whatsapp_setup_in_progress' => false,
        ]);

        Log::warning("WhatsApp setup rolled back", [
            'team_id' => $team->id,
            'to_state' => $toState->value,
        ]);
    }

    /**
     * Reset setup state completely
     */
    public function reset(Team $team): void
    {
        $team->update([
            'whatsapp_setup_state' => WhatsAppSetupState::NOT_CONFIGURED->value,
            'whatsapp_setup_progress' => null,
            'whatsapp_setup_started_at' => null,
            'whatsapp_setup_completed_at' => null,
            'whatsapp_setup_in_progress' => false,
            'whatsapp_setup_retry_count' => 0,
        ]);

        Log::info("WhatsApp setup reset", ['team_id' => $team->id]);
    }

    /**
     * Check if setup is stuck (exceeded timeout)
     */
    public function isStuck(Team $team): bool
    {
        if (!$team->whatsapp_setup_in_progress || !$team->whatsapp_setup_started_at) {
            return false;
        }

        $currentState = $this->getCurrentState($team);
        $timeout = $currentState->getTimeout();

        if ($timeout === 0) {
            return false; // No timeout for this state
        }

        $elapsed = $team->whatsapp_setup_started_at->diffInSeconds(now());
        return $elapsed > $timeout;
    }

    /**
     * Acquire lock for concurrent setup prevention
     */
    public function acquireLock(Team $team): bool
    {
        return DB::transaction(function () use ($team) {
            $team = $team->lockForUpdate()->fresh();

            if ($team->whatsapp_setup_in_progress) {
                return false; // Already locked
            }

            $team->update(['whatsapp_setup_in_progress' => true]);
            return true;
        });
    }

    /**
     * Release lock
     */
    public function releaseLock(Team $team): void
    {
        $team->update(['whatsapp_setup_in_progress' => false]);
    }

    /**
     * Log state transition to audit trail
     */
    protected function logTransition(Team $team, WhatsAppSetupState $from, WhatsAppSetupState $to, array $metadata): void
    {
        WhatsAppSetupAudit::create([
            'team_id' => $team->id,
            'user_id' => auth()->id(),
            'action' => 'state_transition',
            'status' => 'success',
            'metadata' => [
                'from' => $from->value,
                'to' => $to->value,
                'data' => $metadata,
            ],
            'ip_address' => request()->ip(),
            'reference_id' => WhatsAppSetupAudit::generateReferenceId(),
        ]);
    }

    /**
     * Trigger actions when entering a state
     */
    protected function onStateEnter(Team $team, WhatsAppSetupState $state, array $metadata): void
    {
        match ($state) {
            WhatsAppSetupState::SUSPENDED => $this->onSuspended($team),
            WhatsAppSetupState::DEGRADED => $this->onDegraded($team),
            WhatsAppSetupState::ACTIVE => $this->onActive($team),
            WhatsAppSetupState::TOKEN_EXPIRED => $this->onTokenExpired($team),
            default => null,
        };
    }

    /**
     * Handle SUSPENDED state entry
     */
    protected function onSuspended(Team $team): void
    {
        // Pause all running campaigns
        $pausedCount = $team->campaigns()->where('status', 'running')->update(['status' => 'paused']);

        Log::critical("WhatsApp account suspended for team {$team->id}", [
            'campaigns_paused' => $pausedCount,
        ]);

        // TODO: Send critical notification to team owner
    }

    /**
     * Handle DEGRADED state entry
     */
    protected function onDegraded(Team $team): void
    {
        Log::warning("WhatsApp account degraded for team {$team->id}");

        // TODO: Send warning notification to team owner
    }

    /**
     * Handle ACTIVE state entry
     */
    protected function onActive(Team $team): void
    {
        Log::info("WhatsApp account activated for team {$team->id}");

        // TODO: Send success notification to team owner
    }

    /**
     * Handle TOKEN_EXPIRED state entry
     */
    protected function onTokenExpired(Team $team): void
    {
        Log::warning("WhatsApp token expired for team {$team->id}");

        // TODO: Attempt auto-refresh or notify owner
    }
}

<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * OfferAuditService
 * ═════════════════
 * Centralised audit trail for every action that touches offer eligibility,
 * trial assignment, or billing override fields.
 *
 * Every method writes a structured record to `audit_logs` with:
 *  - The authenticated Super Admin who performed the action
 *  - The target team and/or user
 *  - The before/after values of any changed fields
 *  - IP address, user-agent, and timestamp (via AuditLog model)
 *
 * Event naming convention: Offer.<Actor>.<Action>
 *   e.g. Offer.SuperAdmin.ExcludeTeam
 *        Offer.System.TrialAssigned
 *        Offer.API.TamperAttempt
 *        Offer.Observer.ClaimProtected
 */
class OfferAuditService
{
    // ------------------------------------------------------------------
    // System-originated events (no actor = automated system action)
    // ------------------------------------------------------------------

    /**
     * Fired when the system successfully assigns the trial offer during signup.
     */
    public static function logTrialAssigned(Team $team, User $owner, string $via = 'signup'): void
    {
        static::write(
            event: 'Offer.System.TrialAssigned',
            team: $team,
            actor: null,
            data: [
                'via' => $via,
                'trial_ends_at' => $team->trial_ends_at?->toDateTimeString(),
                'offer_claimed_at' => $team->offer_claimed_at?->toDateTimeString(),
                'owner_email' => $owner->email,
                'owner_id' => $owner->id,
            ]
        );
    }

    /**
     * Fired when a claim is blocked because the owner already claimed before.
     */
    public static function logClaimBlocked(Team $team, string $reason): void
    {
        static::write(
            event: 'Offer.System.ClaimBlocked',
            team: $team,
            actor: null,
            data: ['reason' => $reason]
        );
    }

    /**
     * Fired by TeamObserver when a system/API attempt to clear offer_claimed_at
     * is blocked and restored.
     */
    public static function logClaimProtected(Team $team, string $attemptedBy = 'unknown'): void
    {
        static::write(
            event: 'Offer.Observer.ClaimProtected',
            team: $team,
            actor: null,
            data: [
                'attempted_by' => $attemptedBy,
                'original' => $team->getOriginal('offer_claimed_at'),
                'attempted' => $team->getDirty()['offer_claimed_at'] ?? null,
            ]
        );
    }

    // ------------------------------------------------------------------
    // Super Admin override events (always require actor attribution)
    // ------------------------------------------------------------------

    /**
     * Fired when a Super Admin manually excludes a team from the offer.
     */
    public static function logTeamExcluded(Team $team, User $admin, string $reason = ''): void
    {
        static::write(
            event: 'Offer.SuperAdmin.ExcludeTeam',
            team: $team,
            actor: $admin,
            data: ['reason' => $reason]
        );
    }

    /**
     * Fired when a Super Admin re-includes a previously excluded team.
     */
    public static function logTeamIncluded(Team $team, User $admin): void
    {
        static::write(
            event: 'Offer.SuperAdmin.IncludeTeam',
            team: $team,
            actor: $admin,
            data: []
        );
    }

    /**
     * Fired when a Super Admin uses toggleOffer() or the CRM to assign a trial.
     */
    public static function logAdminTrialAssigned(Team $team, User $admin): void
    {
        static::write(
            event: 'Offer.SuperAdmin.TrialAssigned',
            team: $team,
            actor: $admin,
            data: [
                'trial_ends_at' => $team->trial_ends_at?->toDateTimeString(),
            ]
        );
    }

    /**
     * Fired when a Super Admin promotes a team from trial to active.
     */
    public static function logAdminTrialPromo(Team $team, User $admin): void
    {
        static::write(
            event: 'Offer.SuperAdmin.PromoteToActive',
            team: $team,
            actor: $admin,
            data: []
        );
    }

    /**
     * Fired when a Super Admin creates a tenant manually via the dashboard.
     */
    public static function logManualTenantCreated(Team $team, User $admin, bool $offerApplied): void
    {
        static::write(
            event: 'Offer.SuperAdmin.ManualTenantCreated',
            team: $team,
            actor: $admin,
            data: [
                'offer_applied' => $offerApplied,
                'subscription_status' => $team->subscription_status,
                'subscription_plan' => $team->subscription_plan,
            ]
        );
    }

    /**
     * Fired when an API caller attempts to write a protected field.
     * (Also written directly by BlockTrialFieldsViaApi middleware.)
     */
    public static function logApiTamperAttempt(array $fields, ?User $actor, string $endpoint): void
    {
        static::write(
            event: 'Offer.API.TamperAttempt',
            team: null,
            actor: $actor,
            data: [
                'fields' => $fields,
                'endpoint' => $endpoint,
                'ip' => Request::ip(),
            ]
        );
    }

    /**
     * Fired when a Super Admin force-assigns a trial, bypassing eligibility rules.
     */
    public static function logTrialForced(Team $team, User $admin, int $months, \Carbon\Carbon $endsAt, string $reason): void
    {
        static::write(
            event: 'Offer.SuperAdmin.TrialForced',
            team: $team,
            actor: $admin,
            data: [
                'months' => $months,
                'trial_ends_at' => $endsAt->toDateTimeString(),
                'reason' => $reason,
                'eligibility_bypassed' => true,
                'previous_status' => $team->getOriginal('subscription_status') ?? $team->subscription_status,
            ]
        );
    }

    /**
     * Fired when a Super Admin extends an existing trial.
     */
    public static function logTrialExtended(Team $team, User $admin, int $days, \Carbon\Carbon $newEndsAt, string $reason): void
    {
        static::write(
            event: 'Offer.SuperAdmin.TrialExtended',
            team: $team,
            actor: $admin,
            data: [
                'days_added' => $days,
                'new_trial_ends_at' => $newEndsAt->toDateTimeString(),
                'old_trial_ends_at' => $team->trial_ends_at?->toDateTimeString(),
                'reason' => $reason,
            ]
        );
    }

    /**
     * Fired when a Super Admin immediately revokes a trial.
     */
    public static function logTrialRevoked(Team $team, User $admin, string $reason): void
    {
        static::write(
            event: 'Offer.SuperAdmin.TrialRevoked',
            team: $team,
            actor: $admin,
            data: [
                'previous_status' => $team->getOriginal('subscription_status') ?? $team->subscription_status,
                'previous_ends_at' => $team->trial_ends_at?->toDateTimeString(),
                'reason' => $reason,
            ]
        );
    }

    /**
     * Fired when a Super Admin manually converts a team to an active paid plan.
     */
    public static function logManualConversion(Team $team, User $admin, string $plan, ?\Carbon\Carbon $endsAt, string $reason): void
    {
        static::write(
            event: 'Offer.SuperAdmin.ManualConversion',
            team: $team,
            actor: $admin,
            data: [
                'plan' => $plan,
                'subscription_ends_at' => $endsAt?->toDateTimeString(),
                'previous_status' => $team->getOriginal('subscription_status') ?? 'trial',
                'reason' => $reason,
            ]
        );
    }

    /**
     * Fired when a Super Admin saves changes to the offer settings page.
     *
     * Records:
     *  - Which specific keys changed (before/after values)
     *  - Impact classification: 'new_signups_only' | 'live_trial_teams' | 'immediate_global'
     *  - Admin attribution
     */
    public static function logSettingsChanged(User $admin, array $changes): void
    {
        if (empty($changes)) {
            return;
        }

        // Classify each changed key so the audit log is self-explanatory
        // ─────────────────────────────────────────────────────────────
        // new_signups_only   : stamped once at signup; existing tenants unaffected
        // live_trial_teams   : read at runtime; changes apply to all current trial teams
        // immediate_global   : changes the offer gate itself (enable/disable)

        $writeOnce = [
            'offer_trial_months',   // stamped into trial_ends_at at signup
            'offer_initial_credit', // deposited once as wallet credit at signup
        ];
        $liveRuntime = [
            'offer_message_limit',
            'offer_agent_limit',
            'offer_whatsapp_limit',
            'offer_contacts_limit',
            'offer_campaign_limit',
            'offer_automation_limit',
            'offer_call_minutes_limit',
            'offer_included_features',
            'offer_start_at',
            'offer_end_at',
            'identity_domain_limit',
            'identity_public_domains',
        ];

        $classified = [];
        foreach ($changes as $key => $diff) {
            $classified[$key] = array_merge($diff, [
                'impact' => in_array($key, $writeOnce, true)
                    ? 'new_signups_only'
                    : (in_array($key, $liveRuntime, true)
                        ? 'live_trial_teams'
                        : 'immediate_global'),
            ]);
        }

        static::write(
            event: 'Offer.SuperAdmin.SettingsChanged',
            team: null,
            actor: $admin,
            data: [
                'changes' => $classified,
                'changed_keys' => array_keys($changes),
                'change_count' => count($changes),
            ]
        );
    }

    // ------------------------------------------------------------------
    // Internal writer
    // ------------------------------------------------------------------

    private static function write(
        string $event,
        ?Team $team,
        ?User $actor,
        array $data,
    ): void {
        try {
            AuditLog::create([
                'user_id' => $actor?->id ?? (auth()->check() ? auth()->id() : null),
                'team_id' => $team?->id,
                'event_type' => $event,
                'identifier' => $team ? "team:{$team->id}" : 'system',
                'provider' => $actor ? 'super_admin' : 'system',
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'metadata' => array_merge($data, [
                    'admin_email' => $actor?->email,
                    'admin_id' => $actor?->id,
                    'team_name' => $team?->name,
                ]),
            ]);
        } catch (\Throwable $e) {
            // Audit logging should never break the primary flow
            Log::error('OfferAuditService: failed to write audit log', [
                'event' => $event,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}

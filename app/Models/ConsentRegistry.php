<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RuntimeException;

/**
 * ConsentRegistry — Immutable, Append-Only Compliance Ledger
 * ═══════════════════════════════════════════════════════════
 *
 * This model is intentionally restrictive:
 *   - Rows can only be CREATED, never UPDATED or DELETED.
 *   - The most-recent row for a (team_id, phone_number) pair is always
 *     the authoritative consent state.
 *   - All writes go through ConsentService to ensure consistent sourcing.
 *
 * GDPR Article 7 / WhatsApp Business Policy compliance:
 * An opt-out (STOP) recorded here MUST be honoured even if the contacts
 * table was overwritten by a backup restore. The BackupService reads this
 * registry after every restore and re-applies the latest decision.
 *
 * @property int         $id
 * @property int         $team_id
 * @property int|null    $contact_id
 * @property string      $phone_number
 * @property string      $action          OPT_IN | OPT_OUT | OPT_IN_ATTEMPT | OPT_OUT_ATTEMPT | CONSENT_EXPIRED | CONSENT_RENEWED
 * @property string|null $source          STOP_KEYWORD | MANUAL_ADMIN | API | START_KEYWORD …
 * @property string|null $channel         whatsapp | sms | web …
 * @property string|null $notes
 * @property string|null $proof_url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon      $recorded_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class ConsentRegistry extends Model
{
    use HasFactory;
    use \App\Traits\HasTeam;

    protected $table = 'consent_registry';

    /**
     * Only allow mass-assignment on creation — never on update.
     */
    protected $fillable = [
        'team_id',
        'contact_id',
        'phone_number',
        'action',
        'source',
        'channel',
        'notes',
        'proof_url',
        'ip_address',
        'user_agent',
        'expires_at',
        'recorded_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'recorded_at' => 'datetime',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // IMMUTABILITY ENFORCEMENT
    // ──────────────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        /**
         * Block any attempt to UPDATE an existing row.
         * This is the model-layer enforcement of the "append-only" contract.
         * Even a super-admin call to ConsentRegistry::find($id)->update([…])
         * will throw, preventing accidental mutation.
         */
        static::updating(function (ConsentRegistry $model) {
            throw new RuntimeException(
                'ConsentRegistry is immutable. Consent records cannot be modified after creation. ' .
                'Create a new registry entry instead. ' .
                '[GDPR Compliance Violation Prevention — Consent Ledger]'
            );
        });

        /**
         * Block any attempt to DELETE an existing row via Eloquent.
         */
        static::deleting(function (ConsentRegistry $model) {
            throw new RuntimeException(
                'ConsentRegistry rows cannot be deleted. ' .
                'Deletion of consent records is a GDPR compliance violation. ' .
                '[GDPR Compliance Violation Prevention — Consent Ledger]'
            );
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────────────────────



    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // QUERY HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get the most-recent authoritative consent decision for a phone number
     * within a tenant. This is the "time-based authoritative" lookup used by
     * BackupService after a restore to rehydrate the contacts table.
     *
     * Only OPT_IN and OPT_OUT are considered authoritative states.
     * Attempt rows are skipped — they are audit breadcrumbs only.
     */
    public static function getLatestAuthoritativeState(int $teamId, string $phoneNumber): ?self
    {
        return static::where('team_id', $teamId)
            ->where('phone_number', $phoneNumber)
            ->whereIn('action', ['OPT_IN', 'OPT_OUT'])
            ->latest('created_at')
            ->first();
    }

    /**
     * Check if the most-recent authoritative state represents an opt-out.
     */
    public static function isOptedOut(int $teamId, string $phoneNumber): bool
    {
        $latest = static::getLatestAuthoritativeState($teamId, $phoneNumber);
        return $latest && $latest->action === 'OPT_OUT';
    }

    /**
     * Scope to opted-out contacts for a team — for bulk enforcement.
     */
    public function scopeLatestOptOuts($query, int $teamId)
    {
        // Sub-select: latest action per phone_number, filtered to OPT_OUT
        return $query
            ->where('team_id', $teamId)
            ->whereIn('action', ['OPT_IN', 'OPT_OUT'])
            ->whereRaw('created_at = (
                SELECT MAX(cr2.created_at)
                FROM consent_registry cr2
                WHERE cr2.team_id = consent_registry.team_id
                  AND cr2.phone_number = consent_registry.phone_number
                  AND cr2.action IN (\'OPT_IN\', \'OPT_OUT\')
            )')
            ->where('action', 'OPT_OUT');
    }
}

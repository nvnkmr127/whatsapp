<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TeamTransaction — Immutable Financial Ledger
 * ════════════════════════════════════════════════════════
 * This model is the single source of truth for all monetary events
 * (deposits, usage charges, refunds, subscription fees, call charges).
 *
 * DESIGN PRINCIPLE: External Ledger / Append-Only
 * ────────────────────────────────────────────────
 * Financial ledgers in SaaS must be:
 *   - APPEND-ONLY:   New entries are added; old entries are never changed.
 *   - IMMUTABLE:     Once written, a transaction record cannot be modified or deleted.
 *   - AUDITABLE:     The running balance at any point-in-time can be reconstructed
 *                    by summing `amount` for all transactions up to that timestamp.
 *
 * This prevents:
 *   ❌ Backup-restore fraud (restoring old backup to wipe usage charges)
 *   ❌ Direct DB manipulation via Eloquent update()/delete() calls
 *   ❌ Financial record tampering by any code path (tenant or admin)
 *
 * IMMUTABILITY ENFORCEMENT:
 *   Overrides performUpdate() and performDeleteOnModel() to throw an
 *   exception if any code attempts to modify or delete a transaction.
 *   This is enforced at the Eloquent ORM level, below controller/service logic.
 * ────────────────────────────────────────────────────────
 */
class TeamTransaction extends Model
{
    // Only allow creation. All other mutations are blocked below.
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ─────────────────────────────────────────────────────────────────────
    // IMMUTABILITY GUARDS
    // These override Eloquent's internal save/delete pipeline.
    // Any attempt to call ->save() on an existing record, ->update(), or
    // ->delete() on any TeamTransaction will throw a RuntimeException.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Block all UPDATE operations on existing transaction records.
     * Called by Eloquent internally whenever ->save() is called on a
     * model that already exists in the database (i.e., has a primary key).
     *
     * @throws \RuntimeException Always.
     */
    protected function performUpdate(\Illuminate\Database\Eloquent\Builder $query): bool
    {
        throw new \RuntimeException(
            'TeamTransaction is an immutable financial ledger. '.
            'Records cannot be updated. Create a correcting entry instead.'
        );
    }

    /**
     * Block all DELETE operations on transaction records.
     * Called by Eloquent internally whenever ->delete() is invoked.
     *
     * @throws \RuntimeException Always.
     */
    protected function performDeleteOnModel(): void
    {
        throw new \RuntimeException(
            'TeamTransaction is an immutable financial ledger. '.
            'Records cannot be deleted. This operation is not permitted.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}

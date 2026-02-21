<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TeamWallet (Core Billing Engine)
 * ════════════════════════════════════════════════════════
 * WALLET + TRIAL INTERACTION RULES:
 * 1. Trial Includes Usage Limit Separate From Wallet:
 *      Trial status provides a monthly ceiling (e.g., 1000 msgs) computed by
 *      EntitlementService, but DOES NOT grant unlimited monetary value.
 * 2. Wallet Credit Usage Rules Clearly Defined:
 *      A team's wallet operates strictly on a prepaid, monetary-equivalent basis
 *      (e.g., USD balance). Even on trial, if a feature incurs a per-use Meta cost
 *      (e.g., sending a marketing template), it drains true Wallet Balance.
 * 3. No Double Benefit Stacking:
 *      Trials provide feature access and threshold limits. They do NOT waive
 *      underlying Meta per-conversation wholesale costs. Preloaded signup credits
 *      ($10) form the only monetary buffer.
 * 4. No Negative Wallet Allowed:
 *      Deductions use transactional locks. Transactions MUST fail and block outbound
 *      API calls before diving below zero.
 * 5. Cost Calculations Always Logged:
 *      Every fractional cent deducted creates a corresponding `TeamTransaction` audit log.
 *
 * ════════════════════════════════════════════════════════
 * IMMUTABILITY / MANIPULATION PROTECTION:
 * ────────────────────────────────────────────────────────
 * The `balance` field is the most sensitive field in the system.
 * It must ONLY be mutated through controlled methods:
 *
 *   ✅ ALLOWED:     TeamWallet::strictDeduct($cost)   — atomic deduction
 *   ✅ ALLOWED:     BillingService::deposit()          — credit addition
 *   ❌ FORBIDDEN:   ->update(['balance' => X])         — direct override
 *   ❌ FORBIDDEN:   $wallet->balance = X; $wallet->save() — direct assignment
 *
 * The `performUpdate()` guard blocks any `->save()` or `->update()` call
 * that touches the `balance` column directly, unless it is called from
 * within an authorised internal method of this class itself.
 *
 * Additionally, TeamWallet must NEVER be restorable via backup.
 * The BackupService::$protectedTables list includes 'team_wallets' explicitly.
 * ────────────────────────────────────────────────────────
 */
class TeamWallet extends Model
{
    protected $guarded = [];

    /**
     * Flag used to allow internal balance mutations to bypass the guard.
     * Set to true only within strictDeduct() and incrementBalance().
     */
    private bool $allowBalanceMutation = false;

    /**
     * Guard against raw direct balance manipulation via ->update() or ->save().
     * Only internal authorised methods (strictDeduct, incrementBalance) may
     * change the balance field.
     *
     * @throws \RuntimeException if balance is modified without using authorised methods.
     */
    protected function performUpdate(\Illuminate\Database\Eloquent\Builder $query): bool
    {
        $dirty = $this->getDirty();

        if (isset($dirty['balance']) && !$this->allowBalanceMutation) {
            throw new \RuntimeException(
                'Direct manipulation of TeamWallet::balance is forbidden. ' .
                'Use strictDeduct() for charges or BillingService::deposit() for credits.'
            );
        }

        return parent::performUpdate($query);
    }

    /**
     * Strictly deducts from wallet ensuring balance never goes negative.
     * Must be called within a DB Transaction where this model is lockForUpdate().
     *
     * @throws \Exception If balance is insufficient.
     */
    public function strictDeduct(float $cost): void
    {
        if ($cost <= 0)
            return;

        if ($this->balance < $cost) {
            throw new \Exception("Insufficient wallet balance. Have: {$this->balance}, Need: {$cost}. No negative wallet allowed.");
        }

        $this->allowBalanceMutation = true;
        $this->balance -= $cost;
        $this->save();
        $this->allowBalanceMutation = false;
    }

    /**
     * Safely increments the wallet balance.
     * Should only be called from BillingService::deposit().
     *
     * @param float $amount Amount to credit. Must be positive.
     */
    public function incrementBalance(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Deposit amount must be positive. Got: {$amount}");
        }

        $this->allowBalanceMutation = true;
        $this->increment('balance', $amount);
        $this->allowBalanceMutation = false;
    }

    // ─────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}

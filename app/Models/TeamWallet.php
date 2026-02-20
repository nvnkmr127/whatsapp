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
 * ────────────────────────────────────────────────────────
 */
class TeamWallet extends Model
{
    protected $guarded = [];

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

        $this->balance -= $cost;
        $this->save();
    }
}

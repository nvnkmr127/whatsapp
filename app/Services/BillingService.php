<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamTransaction;
use App\Models\TeamWallet;
use App\Models\WhatsAppConversation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BillingService
 * ════════════════════════════════════════════════════════
 * CONFIRMED: WALLET + TRIAL INTERACTION LOGIC
 * ────────────────────────────────────────────────────────
 * 1. Trial Includes Usage Limit Separate From Wallet:
 *    - A trial restricts how many units you can consume (e.g. 5,000 active contacts).
 *    - The Wallet funds the underlying Meta wholesale costs. Two separate layers.
 *
 * 2. Wallet Credit Usage Rules Clearly Defined:
 *    - Wallet strictly manages pre-paid balance.
 *    - No differentiation exists between "promotional startup credit" vs "purchased credit".
 *
 * 3. No Double Benefit Stacking:
 *    - Being on a "Free Trial" DOES NOT wave the cost of sending messages/calls.
 *    - The user simply bleeds down the initial signup credit (or top-ups).
 *
 * 4. No Negative Wallet Allowed:
 *    - Evaluated before *any* action (via OutboundPreflightService or internal DB transactions).
 *    - System will block actions rather than dip into negative ledger. (strictDeduct implementation)
 *
 * 5. Cost Calculations Always Logged:
 *    - `TeamTransaction` is ALWAYS emitted when wallet balance materially decreases.
 */
class BillingService
{
    /**
     * Check if team has exceeded plan limits.
     */
    public function checkPlanLimits(Team $team)
    {
        $limit = $team->getPlanLimit('message_limit', 1000);

        if ($limit === 0) {
            return true;
        } // Unlimited

        // 2. Count Usage (Current Month)
        $usage = \App\Models\Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if ($usage >= $limit) {
            return false;
        }

        return true;
    }

    /**
     * Deduct logic for a conversation.
     * Returns true if allowed (balance sufficient), false otherwise.
     */
    public function recordConversationUsage(Team $team, $contactId, $category, $wamid)
    {
        if ((bool) config('app.full_access_all', false)) {
            Log::warning("Global full-access bypass enabled; skipping billing gate for team {$team->id}", [
                'team_id' => $team->id,
                'category' => $category,
            ]);

            return true;
        }

        // 0. Check Plan Limits first (UC-20)
        // If a limit is exceeded, we should return false early.
        // However, if the team has an override or special offer status, this check needs to be robust.
        // The checkPlanLimits method already uses EntitlementService, which respects offer snapshots.
        if (! $this->checkPlanLimits($team)) {
            Log::warning("Team {$team->id} exceeded monthly message limit.");

            return false;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($team, $contactId, $category, $wamid) {
            // 1. Check for existing open window
            // Meta defines 24h session.
            $existing = WhatsAppConversation::where('team_id', $team->id)
                ->where('contact_id', $contactId)
                ->where('category', $category)
                ->where('window_ends_at', '>', now())
                ->lockForUpdate() // Lock to prevent concurrent window creation
                ->first();

            if ($existing) {
                return true; // Window open, no extra charge
            }

            // 2. Determine Cost
            $cost = $this->getCategoryCost($category);

            // 3. Check Balance with Lock
            // We use lockForUpdate() to ensure no other transaction modifies the balance
            // after we read it but before we deduct.
            $wallet = TeamWallet::firstOrCreate(
                ['team_id' => $team->id],
                ['balance' => 0]
            );

            // Lock the wallet row explicitly with a fresh instance
            $wallet = TeamWallet::where('id', $wallet->id)->lockForUpdate()->first();

            // Re-read cost inside lock to be absolutely sure
            $cost = $this->getCategoryCost($category);

            try {
                $wallet->strictDeduct($cost);
            } catch (\Exception $e) {
                // Strict Prepaid: Block. No negative wallet allowed.
                // UNLESS the team has a 'negative_balance_allowed' override or feature.
                // For now, strict prepaid is the business rule.
                return false;
            }

            TeamTransaction::create([
                'team_id' => $team->id,
                'amount' => -$cost, // Negative
                'type' => 'usage_charge',
                'description' => "New {$category} conversation",
            ]);

            // 5. Open Window
            WhatsAppConversation::create([
                'team_id' => $team->id,
                'contact_id' => $contactId,
                'category' => $category,
                'wamid_start' => $wamid,
                'cost' => $cost,
                'window_starts_at' => now(),
                'window_ends_at' => now()->addHours(24),
            ]);

            return true;
        });
    }

    protected function getCategoryCost($category)
    {
        $prices = config('whatsapp.pricing', [
            'marketing' => 0.10,
            'utility' => 0.05,
            'authentication' => 0.03,
            'service' => 0.00,
        ]);

        return $prices[strtolower($category)] ?? 0.00;
    }

    public function getDetailedUsageStats(Team $team): array
    {
        return [
            'messages' => [
                'usage' => \App\Models\Message::where('team_id', $team->id)
                    ->where('direction', 'outbound')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'limit' => $team->getPlanLimit('message_limit', 1000),
                'label' => 'Outbound Messages',
                'type' => 'monthly',
            ],
            'agents' => [
                'usage' => $team->users()->count() + $team->teamInvitations()->count(),
                'limit' => $team->getPlanLimit('agent_limit', 2),
                'label' => 'Team Agents',
                'type' => 'provisioned',
            ],
            'automations' => [
                'usage' => \App\Models\AutomationRun::whereHas('automation', fn ($q) => $q->where('team_id', $team->id))
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'limit' => $team->getPlanLimit('automation_run_limit', 100),
                'label' => 'Automation Runs',
                'type' => 'monthly',
            ],
            'contacts' => [
                'usage' => $team->contacts()->count(),
                'limit' => $team->getPlanLimit('contact_limit', 1000),
                'label' => 'CRM Contacts',
                'type' => 'total',
            ],
            'ai_conversations' => [
                'usage' => \App\Models\ActivityLog::where('team_id', $team->id)
                    ->where('action', 'ai_interaction')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'limit' => $team->getPlanLimit('ai_conversation_limit', 50),
                'label' => 'AI Interactions',
                'type' => 'monthly',
            ],
        ];
    }

    protected static $warningCache = [];

    /**
     * Identify which resources are near or over their limits.
     */
    public function getWarningStatus(Team $team): array
    {
        $cacheId = $team->id;
        if (isset(static::$warningCache[$cacheId])) {
            return static::$warningCache[$cacheId];
        }

        $stats = $this->getDetailedUsageStats($team);
        $warnings = [];
        $alertsToDispatch = [];

        foreach ($stats as $key => $data) {
            if ($data['limit'] === 0) {
                continue;
            } // Unlimited

            $percent = ($data['usage'] / $data['limit']) * 100;
            $level = null;
            $message = '';

            if ($percent >= 100) {
                $level = 'danger';
                $message = "Limit reached! You have used all {$data['limit']} {$data['label']}. Please upgrade for more access.";
            } elseif ($percent >= 90) {
                $level = 'warning';
                $message = "Critical threshold! You are at ".round($percent, 1)."% of your {$data['label']} limit.";
            } elseif ($percent >= 80) {
                $level = 'info';
                $message = "Usage alert: You have reached 80% of your {$data['label']} limit.";
            }

            if ($level) {
                $warnings[] = [
                    'level' => $level,
                    'metric' => $key,
                    'message' => $message,
                    'percent' => $percent,
                ];

                // Check if this specific alert has increased in severity
                $cacheKey = "billing_alert_{$team->id}_{$key}";
                $lastLevel = \Illuminate\Support\Facades\Cache::get($cacheKey);
                $levels = ['info' => 1, 'warning' => 2, 'danger' => 3];
                
                if (! $lastLevel || ($levels[$level] > ($levels[$lastLevel] ?? 0))) {
                    $alertsToDispatch[] = [
                        'key' => $key,
                        'level' => $level,
                        'percent' => $percent,
                        'message' => $message,
                        'levelValue' => $levels[$level]
                    ];
                }
            }
        }

        // Handle Alerting Cooldowns (max 1 per day, max 10 total — then stop)
        if (!empty($alertsToDispatch)) {
            $globalCooldownKey = "billing_alert_global_cooldown_{$team->id}";
            $sendCountKey = "billing_alert_send_count_{$team->id}";

            $hasGlobalCooldown = \Illuminate\Support\Facades\Cache::has($globalCooldownKey);
            $sendCount = (int) \Illuminate\Support\Facades\Cache::get($sendCountKey, 0);

            // Hard stop after 10 lifetime alerts
            if ($sendCount >= 10) {
                return static::$warningCache[$cacheId] = $warnings;
            }

            // Sort by severity (desc) so we pick the most critical one to send
            usort($alertsToDispatch, fn($a, $b) => $b['levelValue'] <=> $a['levelValue']);
            $mostCritical = $alertsToDispatch[0];

            // Only fire if no alert sent in the last 24h
            if (!$hasGlobalCooldown) {
                \App\Events\UsageThresholdReached::dispatch(
                    $team,
                    $mostCritical['key'],
                    $mostCritical['level'],
                    $mostCritical['percent'],
                    $mostCritical['message']
                );

                // Update per-metric cache
                \Illuminate\Support\Facades\Cache::put("billing_alert_{$team->id}_{$mostCritical['key']}", $mostCritical['level'], now()->addHours(24));

                // Update global cooldown and increment lifetime counter
                \Illuminate\Support\Facades\Cache::put($globalCooldownKey, true, now()->addHours(24));
                \Illuminate\Support\Facades\Cache::put($sendCountKey, $sendCount + 1, now()->addDays(90));
            }
        }

        return static::$warningCache[$cacheId] = $warnings;
    }

    public function deposit(Team $team, $amount, $note = 'Deposit')
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Deposit amount must be positive.');
        }

        $wallet = TeamWallet::firstOrCreate(
            ['team_id' => $team->id],
            ['balance' => 0]
        );

        // Use the authorised channel — bypasses the direct-manipulation guard.
        $wallet->incrementBalance($amount);

        TeamTransaction::create([
            'team_id' => $team->id,
            'amount' => $amount,
            'type' => 'deposit',
            'description' => $note,
            'invoice_number' => 'INV-'.Str::ulid(),
        ]);
    }

    /**
     * Create a manual override for a team's billing constraints.
     * Restricted to Super Admins.
     */
    public function createOverride(Team $team, string $type, string $key, $value, string $reason, $durationDays = 30)
    {
        // Permission check should be done in controller/middleware, but double check here.
        if (! auth()->user()?->is_super_admin) {
            throw new \Exception('Unauthorized: Only Super Admins can create billing overrides.');
        }

        $override = \App\Models\BillingOverride::create([
            'team_id' => $team->id,
            'created_by' => auth()->id(),
            'type' => $type,
            'key' => $key,
            'value' => $value,
            'reason' => $reason,
            'expires_at' => $durationDays ? now()->addDays($durationDays) : null,
        ]);

        $this->logBillingEvent($team, 'override_created', "Manual override created for {$key}", [
            'type' => $type,
            'value' => $value,
            'expires_at' => $override->expires_at,
        ]);

        return $override;
    }

    /**
     * Record call usage and deduct from wallet.
     */
    public function recordCallUsage(Team $team, \App\Models\WhatsAppCall $call): bool
    {
        // Only bill completed calls
        if ($call->status !== 'completed' || $call->duration_seconds <= 0) {
            return true;
        }

        // Calculate cost if not already set
        if ($call->cost_amount <= 0) {
            $cost = $call->calculateCost($call->duration_seconds);
            $call->update(['cost_amount' => $cost]);
        } else {
            $cost = $call->cost_amount;
        }

        // Get or create wallet
        $wallet = TeamWallet::firstOrCreate(
            ['team_id' => $team->id],
            ['balance' => 0]
        );

        // Check balance (No negative wallet allowed)
        if ($wallet->balance < $cost) {
            Log::error("CRITICAL: Negative wallet prevented for completed call billing. Team {$team->id} has insufficient funds for call {$call->call_id}.", [
                'team_id' => $team->id,
                'call_id' => $call->call_id,
                'cost' => $cost,
                'balance' => $wallet->balance,
            ]);

            // We do NOT deduct if it would go negative (Rule 4)
            // But we mark the call as 'billing_failed' in metadata if possible
            return false;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $cost, $team, $call) {
            // Lock and deduct
            $lockedWallet = TeamWallet::where('id', $wallet->id)->lockForUpdate()->first();
            $lockedWallet->strictDeduct($cost);

            // Create transaction record (Cost calculations always logged)
            $transactionData = [
                'team_id' => $team->id,
                'amount' => -$cost,
                'type' => 'call_charge',
                'description' => "WhatsApp Call - {$call->formatted_duration} ({$call->direction})",
            ];

            // Check if metadata column exists (resiliency for pending migrations)
            if (\Illuminate\Support\Facades\Schema::hasColumn('team_transactions', 'metadata')) {
                $transactionData['metadata'] = [
                    'call_id' => $call->call_id,
                    'contact_id' => $call->contact_id,
                    'duration_seconds' => $call->duration_seconds,
                ];
            } else {
                // Fallback: Append call ID to description if metadata column is missing
                $transactionData['description'] .= ' [Ref: '.substr($call->call_id, -8).']';
            }

            TeamTransaction::create($transactionData);
        });

        $this->logBillingEvent($team, 'call_charged', "Call billed: {$call->formatted_duration}", [
            'call_id' => $call->call_id,
            'cost' => $cost,
            'duration' => $call->duration_seconds,
        ]);

        return true;
    }

    /**
     * Get call usage statistics for billing period.
     */
    public function getCallUsageStats(Team $team, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $calls = \App\Models\WhatsAppCall::where('team_id', $team->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $completedCalls = $calls->where('status', 'completed');

        return [
            'total_calls' => $calls->count(),
            'completed_calls' => $completedCalls->count(),
            'total_minutes' => round($completedCalls->sum('duration_seconds') / 60, 2),
            'total_cost' => $completedCalls->sum('cost_amount'),
            'inbound_calls' => $calls->where('direction', 'inbound')->count(),
            'outbound_calls' => $calls->where('direction', 'outbound')->count(),
            'failed_calls' => $calls->whereIn('status', ['failed', 'rejected', 'missed'])->count(),
            'average_duration' => $completedCalls->count() > 0
                ? round($completedCalls->avg('duration_seconds'), 0)
                : 0,
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
        ];
    }

    /**
     * Check call usage limits for the team.
     */
    public function checkCallLimits(Team $team): array
    {
        if (! $team->max_call_minutes_per_month) {
            return [
                'has_limit' => false,
                'allowed' => true,
                'minutes_used' => 0,
                'minutes_limit' => null,
                'minutes_remaining' => null,
                'percent_used' => 0,
            ];
        }

        $minutesUsed = \App\Models\WhatsAppCall::where('team_id', $team->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('duration_seconds') / 60;

        $limit = $team->max_call_minutes_per_month;
        $remaining = max(0, $limit - $minutesUsed);
        $percentUsed = $limit > 0 ? ($minutesUsed / $limit) * 100 : 0;

        return [
            'has_limit' => true,
            'allowed' => $minutesUsed < $limit,
            'minutes_used' => round($minutesUsed, 2),
            'minutes_limit' => $limit,
            'minutes_remaining' => round($remaining, 2),
            'percent_used' => round($percentUsed, 2),
        ];
    }

    /**
     * Get call cost breakdown by day for analytics.
     */
    public function getCallCostBreakdown(Team $team, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        $calls = \App\Models\WhatsAppCall::where('team_id', $team->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function ($call) {
                return $call->created_at->format('Y-m-d');
            });

        $breakdown = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayCalls = $calls->get($date, collect());

            $breakdown[] = [
                'date' => $date,
                'calls' => $dayCalls->count(),
                'minutes' => round($dayCalls->sum('duration_seconds') / 60, 2),
                'cost' => $dayCalls->sum('cost_amount'),
            ];
        }

        return $breakdown;
    }
}

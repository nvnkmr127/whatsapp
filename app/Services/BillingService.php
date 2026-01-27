<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamWallet;
use App\Models\TeamTransaction;
use App\Models\WhatsAppConversation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function getUsagePercentage(Team $team)
    {
        $limit = $team->getPlanLimit('message_limit', 1000);

        if ($limit == 0)
            return 0; // Unlimited

        $usage = \App\Models\Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return ($usage / $limit) * 100;
    }

    /**
     * Check if team has exceeded plan limits.
     */
    public function checkPlanLimits(Team $team)
    {
        $limit = $team->getPlanLimit('message_limit', 1000);

        if ($limit === 0)
            return true; // Unlimited

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
        // 0. Check Plan Limits first (UC-20)
        if (!$this->checkPlanLimits($team)) {
            Log::warning("Team {$team->id} exceeded monthly message limit.");
            return false;
        }

        // 1. Check for existing open window
        // Meta defines 24h session.
        $existing = WhatsAppConversation::where('team_id', $team->id)
            ->where('contact_id', $contactId)
            ->where('category', $category)
            ->where('window_ends_at', '>', now())
            ->first();

        if ($existing) {
            return true; // Window open, no extra charge
        }

        // 2. Determine Cost
        $cost = $this->getCategoryCost($category);

        // 3. Check Balance
        $wallet = TeamWallet::firstOrCreate(
            ['team_id' => $team->id],
            ['balance' => 0]
        );

        if ($wallet->balance < $cost) {
            // Strict Prepaid: Block.
            // Postpaid/Enterprise: Allow.
            // For MVP: Allow but go negative? Or Block.
            // Let's Block to be safe unless super admin.
            return false;
        }

        // 4. Deduct & Transact
        $wallet->decrement('balance', $cost);

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
    }

    protected function getCategoryCost($category)
    {
        // Pricing Logic (Could be from Plans or Config)
        // Dummy values
        return match ($category) {
            'marketing' => 0.10, // $0.10
            'utility' => 0.05,
            'authentication' => 0.03,
            'service' => 0.00, // Usually free or first 1000 free
            default => 0.00,
        };
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
                'type' => 'monthly'
            ],
            'agents' => [
                'usage' => $team->users()->count() + $team->teamInvitations()->count(),
                'limit' => $team->getPlanLimit('agent_limit', 2),
                'label' => 'Team Agents',
                'type' => 'provisioned'
            ],
            'automations' => [
                'usage' => \App\Models\AutomationRun::whereHas('automation', fn($q) => $q->where('team_id', $team->id))
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'limit' => $team->getPlanLimit('automation_run_limit', 100),
                'label' => 'Automation Runs',
                'type' => 'monthly'
            ],
            'contacts' => [
                'usage' => $team->contacts()->count(),
                'limit' => $team->getPlanLimit('contact_limit', 1000),
                'label' => 'CRM Contacts',
                'type' => 'total'
            ],
            'ai_conversations' => [
                'usage' => \App\Models\ActivityLog::where('team_id', $team->id)
                    ->where('action', 'ai_interaction')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'limit' => $team->getPlanLimit('ai_conversation_limit', 50),
                'label' => 'AI Interactions',
                'type' => 'monthly'
            ],
        ];
    }

    /**
     * Identify which resources are near or over their limits.
     */
    public function getWarningStatus(Team $team): array
    {
        $stats = $this->getDetailedUsageStats($team);
        $warnings = [];

        foreach ($stats as $key => $data) {
            if ($data['limit'] === 0)
                continue; // Unlimited

            $percent = ($data['usage'] / $data['limit']) * 100;
            $level = null;
            $message = "";

            if ($percent >= 100) {
                $level = 'danger';
                $message = "Limit reached! You have used all {$data['limit']} {$data['label']}. Please upgrade for more access.";
            } elseif ($percent >= 90) {
                $level = 'warning';
                $message = "Critical threshold! You are at {$percent}% of your {$data['label']} limit.";
            } elseif ($percent >= 80) {
                $level = 'info';
                $message = "Usage alert: You have reached 80% of your {$data['label']} limit.";
            }

            if ($level) {
                $warnings[] = [
                    'level' => $level,
                    'metric' => $key,
                    'message' => $message,
                    'percent' => $percent
                ];

                // Cooldown: Only dispatch event if level has increased
                $cacheKey = "billing_alert_{$team->id}_{$key}";
                $lastLevel = \Illuminate\Support\Facades\Cache::get($cacheKey);

                $levels = ['info' => 1, 'warning' => 2, 'danger' => 3];
                if (!$lastLevel || ($levels[$level] > ($levels[$lastLevel] ?? 0))) {
                    \App\Events\UsageThresholdReached::dispatch($team, $key, $level, $percent, $message);
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $level, now()->addHours(24));
                }
            }
        }

        return $warnings;
    }

    public function deposit(Team $team, $amount, $note = 'Deposit')
    {
        $wallet = TeamWallet::firstOrCreate(
            ['team_id' => $team->id],
            ['balance' => 0]
        );

        $wallet->increment('balance', $amount);

        TeamTransaction::create([
            'team_id' => $team->id,
            'amount' => $amount,
            'type' => 'deposit',
            'description' => $note,
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
        ]);
    }

    /**
     * Log a billing-related action for audit purposes.
     */
    public function logBillingEvent(Team $team, string $action, string $description, array $properties = [])
    {
        \App\Models\ActivityLog::create([
            'team_id' => $team->id,
            'user_id' => auth()->id(),
            'action' => "billing.{$action}",
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Create a manual override for a team's billing constraints.
     * Restricted to Super Admins.
     */
    public function createOverride(Team $team, string $type, string $key, $value, string $reason, $durationDays = 30)
    {
        // Permission check should be done in controller/middleware, but double check here.
        if (!auth()->user()?->is_super_admin) {
            throw new \Exception("Unauthorized: Only Super Admins can create billing overrides.");
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
            'expires_at' => $override->expires_at
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

        // Check balance
        if ($wallet->balance < $cost) {
            Log::warning("Insufficient balance for call billing", [
                'team_id' => $team->id,
                'call_id' => $call->call_id,
                'cost' => $cost,
                'balance' => $wallet->balance,
            ]);
            // Allow call to complete but flag for billing
            return false;
        }

        // Deduct cost
        $wallet->decrement('balance', $cost);

        // Create transaction record
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
            $transactionData['description'] .= " [Ref: " . substr($call->call_id, -8) . "]";
        }

        TeamTransaction::create($transactionData);

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
        if (!$team->max_call_minutes_per_month) {
            return [
                'has_limit' => false,
                'allowed' => true,
                'minutes_used' => 0,
                'minutes_limit' => null,
                'minutes_remaining' => null,
                'percent_used' => 0,
            ];
        }

        $currentMonth = now()->format('Y-m');
        $minutesUsed = \App\Models\WhatsAppCall::where('team_id', $team->id)
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])
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
     * Add call usage to detailed usage stats.
     */
    public function getDetailedUsageStatsWithCalls(Team $team): array
    {
        $stats = $this->getDetailedUsageStats($team);

        // Add call usage stats
        $callLimits = $this->checkCallLimits($team);

        if ($callLimits['has_limit']) {
            $stats['call_minutes'] = [
                'usage' => $callLimits['minutes_used'],
                'limit' => $callLimits['minutes_limit'],
                'label' => 'Call Minutes',
                'type' => 'monthly',
            ];
        }

        return $stats;
    }

    /**
     * Generate invoice line items for calls.
     */
    public function getCallInvoiceItems(Team $team, Carbon $startDate, Carbon $endDate): array
    {
        $calls = \App\Models\WhatsAppCall::where('team_id', $team->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($calls->isEmpty()) {
            return [];
        }

        $totalMinutes = round($calls->sum('duration_seconds') / 60, 2);
        $totalCost = $calls->sum('cost_amount');
        $pricePerMinute = config('whatsapp.calling.price_per_minute', 0.005);

        return [
            [
                'description' => 'WhatsApp Voice Calls',
                'quantity' => $totalMinutes,
                'unit' => 'minutes',
                'unit_price' => $pricePerMinute,
                'amount' => $totalCost,
                'details' => [
                    'total_calls' => $calls->count(),
                    'inbound' => $calls->where('direction', 'inbound')->count(),
                    'outbound' => $calls->where('direction', 'outbound')->count(),
                ],
            ],
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

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
}

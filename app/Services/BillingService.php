<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamWallet;
use App\Models\TeamTransaction;
use App\Models\WhatsAppConversation;
use Illuminate\Support\Carbon;

class BillingService
{
    public function getUsagePercentage(Team $team)
    {
        $planName = $team->subscription_plan ?? 'basic';
        $plan = \App\Models\Plan::where('name', $planName)->first();
        $limit = $plan ? $plan->message_limit : 1000;

        if ($limit == 0)
            return 0; // Unlimited?

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
        // 1. Get Plan
        // Assuming we look up Plan by name/slug stored in team->subscription_plan
        // Or we have a Plan model ID. 
        // Migration added 'subscription_plan' string. Let's map it or fetch Plan model.
        // Simple map for MVP if Plan model lookup is complex, but we have Plan model now.
        $planName = $team->subscription_plan ?? 'basic';
        $plan = \App\Models\Plan::where('name', $planName)->first();

        if (!$plan) {
            // Fallback to basic limits if no plan found (or Trial)
            $limit = 1000;
        } else {
            $limit = $plan->message_limit;
        }

        // 2. Count Usage (Current Month)
        // We track 'sent' messages.
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
}

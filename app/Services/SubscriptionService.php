<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Analyze the impact of a plan change before executing.
     */
    public function analyzeImpact(Team $team, string $newPlanName): array
    {
        $currentPlan = Plan::where('name', $team->subscription_plan ?? 'basic')->first();
        $newPlan = Plan::where('name', $newPlanName)->first();

        if (!$newPlan) {
            throw new \Exception("Target plan '{$newPlanName}' not found.");
        }

        $impact = [
            'type' => $newPlan->monthly_price > ($currentPlan->monthly_price ?? 0) ? 'upgrade' : 'downgrade',
            'features_lost' => [],
            'features_gained' => [],
            'resource_impact' => [],
            'is_safe' => true,
        ];

        // 1. Feature Comparison
        $currentFeatures = $currentPlan ? $currentPlan->getEnabledFeatures() : [];
        $newFeatures = $newPlan->getEnabledFeatures();

        $impact['features_lost'] = array_values(array_diff($currentFeatures, $newFeatures));
        $impact['features_gained'] = array_values(array_diff($newFeatures, $currentFeatures));

        // 2. Resource Limit Comparison (Agents)
        $currentAgentCount = $team->users()->count() + $team->teamInvitations()->count();
        if ($currentAgentCount > $newPlan->agent_limit) {
            $impact['resource_impact'][] = [
                'resource' => 'agents',
                'current' => $currentAgentCount,
                'limit' => $newPlan->agent_limit,
                'excess' => $currentAgentCount - $newPlan->agent_limit,
                'message' => "You have {$currentAgentCount} agents, but the new plan only allows {$newPlan->agent_limit}. Excess agents will lose access after the grace period.",
            ];
            $impact['is_safe'] = false;
        }

        return $impact;
    }

    /**
     * Execute the plan change with grace period handling.
     */
    public function changePlan(Team $team, string $newPlanName): bool
    {
        $currentPlanName = $team->subscription_plan ?? 'basic';
        $impact = $this->analyzeImpact($team, $newPlanName);

        DB::transaction(function () use ($team, $newPlanName, $impact) {
            $team->subscription_plan = $newPlanName;
            $team->subscription_status = 'active';

            // Set grace period if downgrade or resource overage
            if ($impact['type'] === 'downgrade' || !$impact['is_safe']) {
                // column subscription_grace_ends_at (pending migration)
                // We will attempt to save it, but if migration failed, this might throw.
                // For now, let's just log it if we can't save.
                try {
                    $team->subscription_grace_ends_at = now()->addDays(7);
                } catch (\Exception $e) {
                    Log::warning("Could not set subscription_grace_ends_at. Is the migration pending?");
                }
            } else {
                try {
                    $team->subscription_grace_ends_at = null;
                } catch (\Exception $e) {
                }
            }

            $team->save();

            // Log activity
            Log::info("Team {$team->id} changed plan from {$currentPlanName} to {$newPlanName}.");
        });

        return true;
    }

    /**
     * Cleanup / Enforcement after grace period.
     * Usually run via a scheduled job.
     */
    public function enforceLimits(Team $team)
    {
        if ($team->subscription_grace_ends_at && $team->subscription_grace_ends_at->isPast()) {
            // Hard enforce: No grace period left.
            // 1. We don't remove agents automatically (destructive), 
            // but we block their login/usage via middleware if they are over-limit.

            // 2. Clear the grace period flag
            $team->subscription_grace_ends_at = null;
            $team->save();

            Log::info("Grace period ended for Team {$team->id}. Hard limits enforced.");
        }
    }
}

<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class AssignmentService
{
    /**
     * Assign a contact to an agent based on deterministic rules.
     */
    public function assign(Contact $contact): ?User
    {
        // 0. Safety Checks
        if ($contact->assigned_to) {
            return $contact->assigned_to_user;
        }

        $result = $this->determineAgent($contact);

        if ($result['agent']) {
            $this->performAssignment($contact, $result['agent'], $result['reason']);
            return $result['agent'];
        }

        return null;
    }

    /**
     * Simulate assignment for a contact without persisting changes.
     * 
     * @return array [
     *    'status' => 'success'|'no_agent'|'error',
     *    'agent_name' => string|null,
     *    'reason' => string,
     *    'rule_matched' => string|null
     * ]
     */
    public function simulate(Contact $contact): array
    {
        $result = $this->determineAgent($contact);

        if ($result['agent']) {
            return [
                'status' => 'success',
                'agent_name' => $result['agent']->name,
                'reason' => $result['reason'],
                'rule_matched' => $result['rule_id'] ?? null,
            ];
        }

        return [
            'status' => 'no_agent',
            'agent_name' => null,
            'reason' => 'No matching agents found or no rules matched.',
            'rule_matched' => null,
        ];
    }

    /**
     * Core logic to determine the agent.
     * Returns ['agent' => User|null, 'reason' => string, 'rule_id' => int|null]
     */
    protected function determineAgent(Contact $contact): array
    {
        $team = $contact->team;
        $config = $team->chat_assignment_config ?? [];

        // 1. Sticky Assignment
        if ($this->shouldStickyAssign($contact, $config)) {
            $previousAgent = $this->getPreviousAgent($contact);
            if ($previousAgent && $this->isAgentAvailable($previousAgent)) {
                return ['agent' => $previousAgent, 'reason' => 'Sticky Assignment (Previous Owner)', 'rule_id' => 'sticky'];
            }
        }

        // 2. Custom Rule Matching
        $ruleAgent = $this->matchRules($contact, $config['rules'] ?? []);
        if ($ruleAgent) {
            return [
                'agent' => $ruleAgent,
                'reason' => "Custom Rule Match",
                'rule_id' => 'custom'
            ];
        }

        // 3. Fallback: Deterministic Round Robin
        $fallbackAgent = $this->getRoundRobinAgent($team);
        if ($fallbackAgent) {
            return ['agent' => $fallbackAgent, 'reason' => 'Round Robin (Load Balanced)', 'rule_id' => 'fallback'];
        }

        return ['agent' => null, 'reason' => 'No eligible agent found', 'rule_id' => null];
    }

    /**
     * Check if sticky assignment is enabled and applicable.
     */
    protected function shouldStickyAssign(Contact $contact, array $config): bool
    {
        return !empty($config['sticky_enabled']);
    }

    /**
     * Get the last agent who handled this contact.
     */
    protected function getPreviousAgent(Contact $contact): ?User
    {
        // 1. Try to find from Conversation History
        // We look for the most recent conversation that was assigned to someone
        $lastConversation = $contact->conversations()
            ->whereNotNull('assigned_to')
            ->latest('created_at')
            ->first();

        if ($lastConversation && $lastConversation->assignee) {
            return $lastConversation->assignee;
        }

        return null;
    }

    /**
     * Evaluate custom assignment rules.
     * Rules structure: [{priority: 1, conditions: [], assign_to: userId/role}]
     */
    protected function matchRules(Contact $contact, array $rules): ?User
    {
        // Sort rules by priority (High to Low)
        $sortedRules = collect($rules)->sortByDesc('priority');

        foreach ($sortedRules as $rule) {
            if ($this->evaluateConditions($contact, $rule['conditions'] ?? [])) {
                return $this->resolveTarget($contact->team, $rule['assign_to'] ?? null);
            }
        }

        return null;
    }

    /**
     * Evaluate a set of conditions against the contact.
     */
    protected function evaluateConditions(Contact $contact, array $conditions): bool
    {
        if (empty($conditions))
            return false;

        foreach ($conditions as $condition) {
            $match = match ($condition['type'] ?? '') {
                'tag' => $contact->tags->contains('name', $condition['value']),
                'source' => $contact->source === $condition['value'],
                'phone_country' => str_starts_with($contact->phone, $condition['value']),
                default => false
            };

            if (!$match)
                return false;
        }

        return true;
    }

    /**
     * Determine the specific user from a rule target.
     */
    protected function resolveTarget(Team $team, $target): ?User
    {
        if (!$target)
            return null;

        if (($target['type'] ?? '') === 'user') {
            $user = $team->users()->where('users.id', $target['id'])->first();
            return $this->isAgentAvailable($user) ? $user : null;
        }

        if (($target['type'] ?? '') === 'role') {
            // Pick best agent with this role
            return $this->getRoundRobinAgent($team, function ($q) use ($target) {
                $q->wherePivot('role', $target['role']);
            });
        }

        return null;
    }

    /**
     * Get the best agent based on load balancing.
     * Deterministic tie-breaking using ID.
     */
    protected function getRoundRobinAgent(Team $team, ?callable $filter = null): ?User
    {
        $query = $team->users()->wherePivot('receives_tickets', true);

        if ($filter) {
            $filter($query);
        }

        $agents = $query->get();

        if ($agents->isEmpty()) {
            return null;
        }

        // 1. Calculate Load
        // We use a simplified load count here to avoid N+1 issues in loop
        // Ideally this should be a subquery or cache
        $agentsWithLoad = $agents->map(function ($agent) use ($team) {
            return [
                'user' => $agent,
                'load' => Contact::where('team_id', $team->id)
                    ->where('assigned_to', $agent->id)
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->count()
            ];
        });

        // 2. Sort: Lowest Load -> Lowest ID (Deterministic)
        $best = $agentsWithLoad->sort(function ($a, $b) {
            if ($a['load'] === $b['load']) {
                return $a['user']->id <=> $b['user']->id;
            }
            return $a['load'] <=> $b['load'];
        })->first();

        return $best['user'] ?? null;
    }

    /**
     * Check if an agent is available (e.g. not away, within limit).
     */
    protected function isAgentAvailable(?User $user): bool
    {
        // Add check for "Away" status or max concurrency limit
        return $user && $user->membership->receives_tickets;
    }

    /**
     * Apply the assignment and create a log.
     */
    protected function performAssignment(Contact $contact, User $user, string $reason): void
    {
        $contact->update(['assigned_to' => $user->id]);

        $contact->notes()->create([
            'team_id' => $contact->team_id,
            'user_id' => $user->id,
            'body' => "System Assignment: Assigned to {$user->name} via {$reason}.",
            'type' => 'system'
        ]);
    }
}

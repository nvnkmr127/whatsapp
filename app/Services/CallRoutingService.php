<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CallRoutingService
{
    protected $team;

    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    /**
     * Find the best agent(s) for a call.
     */
    public function findAgent(Contact $contact, array $options = []): array
    {
        $config = $this->team->getCallRoutingConfig();

        // 1. Check Sticky Assignment
        if ($contact->assigned_to) {
            $currentAgent = $contact->assigned_to_user;
            if ($currentAgent && $currentAgent->isAvailableForCalls($this->team)) {
                return [
                    'agent' => $currentAgent,
                    'method' => 'sticky',
                    'reason' => 'Contact is already assigned to this available agent.',
                ];
            }
        }

        // 2. Execute Routing Mode
        $result = match ($config['mode']) {
            'round_robin' => $this->getRoundRobinAgent(),
            'role_based' => $this->getRoleAgent($config['role'] ?? 'agent'),
            'broadcast' => $this->getBroadcastAgents(),
            default => $this->getFallbackAgent(),
        };

        if ($result['agent'] || !empty($result['agents'])) {
            return $result;
        }

        // 3. Fallback
        return $this->handleFallback($contact);
    }

    /**
     * Get the available agent who hasn't received a call for the longest time.
     */
    protected function getRoundRobinAgent(): array
    {
        $agents = $this->getAvailableAgents();

        if ($agents->isEmpty()) {
            return ['agent' => null, 'method' => 'round_robin', 'reason' => 'No agents available.'];
        }

        // Sort by last_call_ended_at (nulls first) then by ID
        $bestAgent = $agents->sortBy(function ($agent) {
            return [
                $agent->membership->last_call_ended_at ? $agent->membership->last_call_ended_at->timestamp : 0,
                $agent->id
            ];
        })->first();

        return [
            'agent' => $bestAgent,
            'method' => 'round_robin',
            'reason' => 'Agent selected via round-robin distribution.',
        ];
    }

    /**
     * Get available agents with a specific role.
     */
    protected function getRoleAgent(string $role): array
    {
        $agents = $this->getAvailableAgents()
            ->filter(function ($agent) use ($role) {
                return $agent->membership->role === $role;
            });

        if ($agents->isEmpty()) {
            return ['agent' => null, 'method' => 'role_based', 'reason' => "No available agents with role: {$role}"];
        }

        // Default to round-robin among role agents
        $bestAgent = $agents->sortBy(function ($agent) {
            return [
                $agent->membership->last_call_ended_at ? $agent->membership->last_call_ended_at->timestamp : 0,
                $agent->id
            ];
        })->first();

        return [
            'agent' => $bestAgent,
            'method' => 'role_based',
            'reason' => "Agent selected from role: {$role}",
        ];
    }

    /**
     * Get all available agents for broadcast notification.
     */
    protected function getBroadcastAgents(): array
    {
        $agents = $this->getAvailableAgents();

        return [
            'agents' => $agents,
            'method' => 'broadcast',
            'reason' => 'Broadcast notification to all available agents.',
        ];
    }

    /**
     * Handle fallback when no agents are available.
     */
    protected function handleFallback(Contact $contact): array
    {
        $config = $this->team->getCallRoutingConfig();
        $fallback = $config['fallback_action'] ?? 'auto_reply';

        Log::info("Call fallback triggered", [
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
            'action' => $fallback,
        ]);

        return [
            'agent' => null,
            'method' => 'fallback',
            'action' => $fallback,
            'reason' => "No agents available. Fallback: {$fallback}",
        ];
    }

    /**
     * Get a list of all available agents in the team.
     */
    protected function getAvailableAgents(): Collection
    {
        return $this->team->users()
            ->get()
            ->filter(function ($user) {
                return $user->isAvailableForCalls($this->team);
            });
    }

    /**
     * Default fallback agent (admin or first available).
     */
    protected function getFallbackAgent(): array
    {
        $admin = $this->team->users()
            ->wherePivot('role', 'admin')
            ->get()
            ->filter(function ($user) {
                return $user->isAvailableForCalls($this->team);
            })
            ->first();

        if ($admin) {
            return ['agent' => $admin, 'method' => 'fallback_admin', 'reason' => 'Routed to available admin as fallback.'];
        }

        return ['agent' => null, 'method' => 'fallback_none', 'reason' => 'No agents or admins available for fallback.'];
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Team;
use App\Models\User;

class CommerceLifecycleService
{
    /**
     * Define the standard allowed transitions and their metadata.
     */
    public function getTransitions(): array
    {
        return [
            'created' => [
                'label' => 'Created',
                'can_move_to' => ['confirmed', 'cancelled'],
                'triggers_message' => true,
                'required_role' => 'editor',
            ],
            'confirmed' => [
                'label' => 'Confirmed',
                'can_move_to' => ['paid', 'fulfilled', 'cancelled'],
                'triggers_message' => true,
                'required_role' => 'editor',
            ],
            'paid' => [
                'label' => 'Paid',
                'can_move_to' => ['fulfilled', 'cancelled'],
                'triggers_message' => true,
                'required_role' => 'admin', // Financial state requires admin
            ],
            'fulfilled' => [
                'label' => 'Fulfilled',
                'can_move_to' => ['returned'],
                'triggers_message' => true,
                'required_role' => 'editor',
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'can_move_to' => [],
                'triggers_message' => true,
                'required_role' => 'editor',
            ],
            'returned' => [
                'label' => 'Returned',
                'can_move_to' => [],
                'triggers_message' => true,
                'required_role' => 'admin',
            ],
        ];
    }

    /**
     * Validate if a transition is safe and allowed.
     */
    public function canTransition(Order $order, string $newStatus, User $user): array
    {
        $currentStatus = $order->status ?? 'created';
        $transitions = $this->getTransitions();

        if (!isset($transitions[$currentStatus])) {
            return ['allowed' => false, 'message' => "Current state [{$currentStatus}] is not tracked in the lifecycle."];
        }

        $config = $transitions[$currentStatus];

        // 1. Check if the transition is logically allowed
        if (!in_array($newStatus, $config['can_move_to'])) {
            return ['allowed' => false, 'message' => "Forbidden transition: Cannot move order from [{$currentStatus}] to [{$newStatus}]."];
        }

        // 2. Check Permissions (RBAC)
        $targetConfig = $transitions[$newStatus] ?? null;
        $requiredRole = $targetConfig['required_role'] ?? 'editor';

        $userRole = $user->teamRole($order->team)->key ?? 'viewer';

        if (!$this->hasSufficientRole($userRole, $requiredRole)) {
            return ['allowed' => false, 'message' => "Insufficient permissions: Required role [{$requiredRole}], you are [{$userRole}]."];
        }

        return ['allowed' => true];
    }

    protected function hasSufficientRole(string $userRole, string $requiredRole): bool
    {
        $hierarchy = ['admin' => 3, 'editor' => 2, 'viewer' => 1];

        $uRank = $hierarchy[$userRole] ?? 0;
        $rRank = $hierarchy[$requiredRole] ?? 2; // Default to editor requirement

        return $uRank >= $rRank;
    }

    /**
     * Check if a status change should trigger a WhatsApp message for a specific team.
     */
    public function shouldNotify(Team $team, string $status): bool
    {
        $config = $team->commerce_config['lifecycle_notifications'] ?? [];

        // If not explicitly configured, use default from transition table
        if (!isset($config[$status])) {
            $transitions = $this->getTransitions();
            return $transitions[$status]['triggers_message'] ?? false;
        }

        return (bool) $config[$status];
    }
}

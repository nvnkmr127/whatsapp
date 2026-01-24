<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Integration;
use App\Models\Product;
use App\Models\WhatsappTemplate;

class CommerceReadinessService
{
    public const STATE_READY = 'READY';
    public const STATE_WARNING = 'WARNING';
    public const STATE_BLOCKED = 'BLOCKED';

    public function evaluate(Team $team): array
    {
        $checks = [];
        $config = $team->commerce_config ?? [];

        // 1. Critical Base Config
        $checks['currency'] = [
            'label' => 'Store Currency',
            'status' => !empty($config['currency']),
            'level' => 'critical',
            'message' => 'Currency must be defined to process payments.'
        ];

        // 2. Payment Readiness
        $hasPaymentGateway = Integration::where('team_id', $team->id)
            ->whereIn('type', ['stripe', 'razorpay', 'paystack', 'paypal'])
            ->where('status', 'active')
            ->exists();

        $codEnabled = !empty($config['cod_enabled']);

        $checks['payments'] = [
            'label' => 'Payment Support',
            'status' => $hasPaymentGateway || $codEnabled,
            'level' => 'critical',
            'message' => 'Neither Cash on Delivery nor a Payment Gateway is enabled.'
        ];

        // 3. Messaging Dependency
        $waConnection = $team->whatsapp_setup_state; // Integrated state
        $canSend = $team->canSendWhatsAppMessages();

        $checks['whatsapp'] = [
            'label' => 'WhatsApp Connection',
            'status' => $canSend,
            'level' => 'critical',
            'message' => 'WhatsApp must be connected and active to send order updates.'
        ];

        // 4. Operational Readiness (Templates)
        $templates = $config['templates'] ?? [];
        $orderPlacedMapped = !empty($templates['placed']);

        $checks['order_placed_template'] = [
            'label' => 'Confirmation Template',
            'status' => $orderPlacedMapped,
            'level' => 'critical',
            'message' => 'The Order Placed notification template is missing.'
        ];

        // 5. Optimization Warnings (AI)
        $aiEnabled = !empty($config['ai_assistant_enabled']);
        $aiKeySet = !empty(get_setting("ai_openai_api_key_{$team->id}"));

        if ($aiEnabled) {
            $checks['ai_readiness'] = [
                'label' => 'AI Assistant Config',
                'status' => $aiKeySet,
                'level' => 'warning',
                'message' => 'AI is enabled but OpenAI key is missing. AI Shop Assistant will not respond.'
            ];
        }

        // 6. Catalog Health
        $productCount = Product::where('team_id', $team->id)->count();
        $checks['catalog'] = [
            'label' => 'Product Catalog',
            'status' => $productCount > 0,
            'level' => 'warning',
            'message' => 'Your catalog is empty. Customers cannot browse or buy products.'
        ];

        return [
            'state' => $this->calculateOverallState($checks),
            'checks' => $checks,
            'score' => $this->calculateScore($checks)
        ];
    }

    protected function calculateOverallState(array $checks): string
    {
        foreach ($checks as $check) {
            if (!$check['status'] && $check['level'] === 'critical') {
                return self::STATE_BLOCKED;
            }
        }

        foreach ($checks as $check) {
            if (!$check['status'] && $check['level'] === 'warning') {
                return self::STATE_WARNING;
            }
        }

        return self::STATE_READY;
    }

    protected function calculateScore(array $checks): int
    {
        $total = count($checks);
        $passed = count(array_filter($checks, fn($c) => $c['status']));
        return (int) (($passed / $total) * 100);
    }

    /**
     * Helper to verify if a feature should be allowed
     */
    public function canPerformAction(Team $team, string $action): bool
    {
        $readiness = $this->evaluate($team);

        if ($readiness['state'] === self::STATE_BLOCKED) {
            return false;
        }

        if ($action === 'ai_shop' && isset($readiness['checks']['ai_readiness'])) {
            return $readiness['checks']['ai_readiness']['status'];
        }

        return true;
    }

    /**
     * Strict enforcement hook.
     * Throws exceptions if the store is not ready for the given context.
     */
    public function enforce(Team $team, string $context): void
    {
        $readiness = $this->evaluate($team);

        if ($readiness['state'] === self::STATE_BLOCKED) {
            $criticalErrors = array_filter($readiness['checks'], fn($c) => !$c['status'] && $c['level'] === 'critical');
            $message = "Store blocked from [{$context}]: " . implode(', ', array_column($criticalErrors, 'message'));

            throw new \Exception($message);
        }
    }

    /**
     * Hook for Product Catalog sellability.
     * Injects readiness logic into product queries.
     */
    public function applyShoppableScope($query, Team $team)
    {
        $readiness = $this->evaluate($team);

        // Rule: If store is BLOCKED, nothing is shoppable (view only or hidden)
        if ($readiness['state'] === self::STATE_BLOCKED) {
            return $query->whereRaw('1=0'); // Force empty result
        }

        // Rule: Stock requirements
        return $query->where('is_active', true)
            ->where('availability', 'in stock');
    }
}

<?php

namespace Database\Seeders;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\AlertRule;
use Illuminate\Database\Seeder;

class AlertEngineSeeder extends Seeder
{
    public function run(): void
    {
        // 1. System Health Alert
        AlertRule::updateOrCreate(
            ['slug' => 'system-health-critical'],
            [
                'name' => 'Critical System Health Issue',
                'alert_type' => AlertType::SYSTEM,
                'severity' => AlertSeverity::EMERGENCY,
                'is_active' => true,
                'template_slug' => 'system-health-alert',
                'throttle_seconds' => 1800, // 30 minutes
                'escalation_path' => [
                    ['level' => 2, 'delay_mins' => 15, 'emails' => ['sre-lead@example.com']],
                    ['level' => 3, 'delay_mins' => 60, 'emails' => ['cto@example.com']],
                ],
                'trigger_conditions' => [
                    'metric' => 'cpu_usage',
                    'threshold' => 95,
                    'duration' => '5m'
                ]
            ]
        );

        // 2. Billing Alert
        AlertRule::updateOrCreate(
            ['slug' => 'billing-quota-reached'],
            [
                'name' => 'Billing Quota Reached',
                'alert_type' => AlertType::BILLING,
                'severity' => AlertSeverity::WARNING,
                'is_active' => true,
                'template_slug' => 'billing-alert',
                'throttle_seconds' => 86400, // 24 hours
                'escalation_path' => null, // No escalation for billing warnings
            ]
        );

        // 3. Security Alert
        AlertRule::updateOrCreate(
            ['slug' => 'security-brute-force'],
            [
                'name' => 'Potential Brute Force Attack',
                'alert_type' => AlertType::SECURITY,
                'severity' => AlertSeverity::CRITICAL,
                'is_active' => true,
                'template_slug' => 'security-alert',
                'throttle_seconds' => 3600, // 1 hour
                'escalation_path' => [
                    ['level' => 2, 'delay_mins' => 30, 'emails' => ['security-officer@example.com']],
                ],
            ]
        );
    }
}

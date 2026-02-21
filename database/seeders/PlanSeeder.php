<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name' => 'basic',
                'monthly_price' => 29.00,
                'message_limit' => 1000,
                'agent_limit' => 2,
                'max_backups_per_team' => 1,
                'max_storage_mb' => 50,
                'cooldown_hours_between_backups' => 168, // 1 week
                'features' => json_encode([
                    'chat' => true,
                    'contacts' => true,
                    'templates' => true,
                    'campaigns' => false,
                    'automations' => false,
                    'analytics' => false,
                    'commerce' => false,
                    'ai' => false,
                    'api_access' => false,
                    'webhooks' => false,
                    'backups' => false,
                    'cloud_backups' => false,
                ])
            ],
            [
                'name' => 'pro',
                'monthly_price' => 99.00,
                'message_limit' => 10000,
                'agent_limit' => 10,
                'max_backups_per_team' => 5,
                'max_storage_mb' => 512,
                'cooldown_hours_between_backups' => 24, // 1 day
                'features' => json_encode([
                    'chat' => true,
                    'contacts' => true,
                    'templates' => true,
                    'campaigns' => true,
                    'automations' => true,
                    'analytics' => true,
                    'commerce' => true,
                    'ai' => true,
                    'api_access' => false,
                    'webhooks' => true,
                    'backups' => true,
                    'cloud_backups' => true,
                ])
            ],
            [
                'name' => 'enterprise',
                'monthly_price' => 299.00,
                'message_limit' => 100000,
                'agent_limit' => 50,
                'max_backups_per_team' => 20,
                'max_storage_mb' => 2048,
                'cooldown_hours_between_backups' => 6, // 6 hours
                'features' => json_encode([
                    'chat' => true,
                    'contacts' => true,
                    'templates' => true,
                    'campaigns' => true,
                    'automations' => true,
                    'analytics' => true,
                    'commerce' => true,
                    'ai' => true,
                    'api_access' => true,
                    'webhooks' => true,
                    'backups' => true,
                    'cloud_backups' => true,
                ])
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }

        $this->command->info('Plans seeded successfully!');
        $this->command->info('- Basic: $29/mo (1,000 messages, 2 agents)');
        $this->command->info('- Pro: $99/mo (10,000 messages, 10 agents)');
        $this->command->info('- Enterprise: $299/mo (100,000 messages, 50 agents)');
    }
}

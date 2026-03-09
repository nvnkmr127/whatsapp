<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkflowTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Workflow::updateOrCreate(
            ['name' => 'Onboarding Safety Net', 'team_id' => null],
            [
                'description' => 'Multi-stage re-engagement sequence to guide new users to full activation.',
                'is_active' => true,
                'trigger_type' => 'user_signed_up',
                'folder' => 'Onboarding',
                'definition' => [
                    [
                        'id' => 'node_1',
                        'delay' => 60, // 1 hour
                        'type' => 'condition',
                        'config' => [
                            'field' => 'onboarding.whatsapp_connected',
                            'operator' => 'eq',
                            'value' => false
                        ],
                        'true_nodes' => [
                            [
                                'id' => 'node_1_remind',
                                'type' => 'action',
                                'action_type' => 'send_whatsapp_template',
                                'config' => [
                                    'template_name' => 'onboarding_remote_reminder',
                                    'language' => 'en_US',
                                    'variables' => ['{name}']
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'node_2',
                        'delay' => 120, // 2 hours
                        'type' => 'condition',
                        'config' => [
                            'field' => 'onboarding.business_profile_completed',
                            'operator' => 'eq',
                            'value' => false
                        ],
                        'true_nodes' => [
                            [
                                'id' => 'node_2_remind',
                                'type' => 'action',
                                'action_type' => 'send_whatsapp_template',
                                'config' => [
                                    'template_name' => 'onboarding_profile_reminder',
                                    'language' => 'en_US',
                                    'variables' => ['{name}']
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'node_3',
                        'delay' => 1440, // 24 hours
                        'type' => 'condition',
                        'config' => [
                            'field' => 'onboarding.first_campaign_created',
                            'operator' => 'eq',
                            'value' => false
                        ],
                        'true_nodes' => [
                            [
                                'id' => 'node_3_remind',
                                'type' => 'action',
                                'action_type' => 'send_whatsapp_template',
                                'config' => [
                                    'template_name' => 'onboarding_campaign_tutorial',
                                    'language' => 'en_US',
                                    'variables' => ['{name}']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );
    }
}

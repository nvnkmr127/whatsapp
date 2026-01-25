<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $settings = [
            ['key' => 'offer_enabled', 'value' => '1', 'group' => 'system'],
            ['key' => 'offer_trial_months', 'value' => '6', 'group' => 'system'],
            ['key' => 'offer_message_limit', 'value' => '5000', 'group' => 'system'], // 5k messages
            ['key' => 'offer_agent_limit', 'value' => '5', 'group' => 'system'],
            ['key' => 'offer_whatsapp_limit', 'value' => '2', 'group' => 'system'], // 2 Numbers
            ['key' => 'offer_initial_credit', 'value' => '5.00', 'group' => 'system'], // $5 Gift
            [
                'key' => 'offer_included_features',
                'value' => json_encode([
                    'chat',
                    'contacts',
                    'templates',
                    'campaigns',
                    'automations',
                    'analytics',
                    'commerce',
                    'ai',
                    'webhooks'
                ]),
                'group' => 'system'
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'offer_enabled',
            'offer_trial_months',
            'offer_message_limit',
            'offer_agent_limit',
            'offer_whatsapp_limit',
            'offer_initial_credit',
            'offer_included_features'
        ])->delete();
    }
};

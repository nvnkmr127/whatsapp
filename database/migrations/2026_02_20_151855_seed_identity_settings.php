<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the two configurable knobs used by UniqueIdentityService
 * into the `settings` table.
 *
 * identity_domain_limit    – max trial teams allowed per business email domain
 * identity_public_domains  – comma-separated extra domains to treat as public
 *                            (will not apply domain uniqueness checks)
 */
return new class extends Migration {
    public function up(): void
    {
        $settings = [
            [
                'key' => 'identity_domain_limit',
                'value' => '1',        // 1 trial account per business domain
                'group' => 'system',
            ],
            [
                'key' => 'identity_public_domains',
                'value' => '',         // comma-separated; empty = use built-in list
                'group' => 'system',
            ],
        ];

        foreach ($settings as $s) {
            DB::table('settings')->updateOrInsert(
                ['key' => $s['key']],
                [
                    'value' => $s['value'],
                    'group' => $s['group'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', ['identity_domain_limit', 'identity_public_domains'])
            ->delete();
    }
};

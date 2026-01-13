<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [

            'source.view',
            'source.create',
            'source.edit',
            'source.delete',

            'ai_prompt.view',
            'ai_prompt.create',
            'ai_prompt.edit',
            'ai_prompt.delete',

            'canned_reply.view',
            'canned_reply.create',
            'canned_reply.edit',
            'canned_reply.delete',

            'connect_account.view',
            'connect_account.connect',
            'connect_account.disconnect',

            'message_bot.view',
            'message_bot.create',
            'message_bot.edit',
            'message_bot.delete',
            'message_bot.clone',

            'template_bot.view',
            'template_bot.create',
            'template_bot.edit',
            'template_bot.delete',
            'template_bot.clone',

            'template.view',
            'template.load_template',

            'campaigns.view',
            'campaigns.create',
            'campaigns.edit',
            'campaigns.delete',
            'campaigns.show_campaign',

            'chat.view',
            'chat.read_only',

            'activity_log.view',
            'activity_log.delete',

            'whatsmark_settings.view',
            'whatsmark_settings.edit',

            'bulk_campaigns.send',

            'role.view',
            'role.create',
            'role.edit',
            'role.delete',

            'status.view',
            'status.create',
            'status.edit',
            'status.delete',

            'contact.view',
            'contact.create',
            'contact.edit',
            'contact.delete',
            'contact.bulk_import',

            'system_settings.view',
            'system_settings.edit',

            'user.view',
            'user.create',
            'user.edit',
            'user.delete',

            'email_template.view',
            'email_template.edit',

        ];

        foreach ($permissions as $permission) {
            Permission::updateOrInsert(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}

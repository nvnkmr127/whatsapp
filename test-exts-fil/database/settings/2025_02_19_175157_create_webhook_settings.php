<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateWebhookSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('webhook.webhook_enabled', false);
        $this->migrator->add('webhook.webhook_url', '');
        $this->migrator->add('webhook.contacts_actions', [
            'create' => false,
            'read'   => false,
            'update' => false,
            'delete' => false,
        ]);
        $this->migrator->add('webhook.status_actions', [
            'create' => false,
            'read'   => false,
            'update' => false,
            'delete' => false,
        ]);
        $this->migrator->add('webhook.source_actions', [
            'create' => false,
            'read'   => false,
            'update' => false,
            'delete' => false,
        ]);
    }

    public function down(): void
    {
        $this->migrator->delete('webhook.webhook_enabled');
        $this->migrator->delete('webhook.webhook_url');
        $this->migrator->delete('webhook.contacts_actions');
        $this->migrator->delete('webhook.status_actions');
        $this->migrator->delete('webhook.source_actions');
    }
}

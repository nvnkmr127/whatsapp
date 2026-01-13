<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('api.enabled', false);
        $this->migrator->add('api.token', null);
        $this->migrator->add('api.token_generated_at', null);
        $this->migrator->add('api.last_used_at', null);
        $this->migrator->add('api.rate_limit_max', null);
        $this->migrator->add('api.rate_limit_decay', null);

        $this->migrator->add('api.abilities', [
            // Contacts Abilities
            'contacts.create',
            'contacts.read',
            'contacts.update',
            'contacts.delete',

            // Status Abilities
            'statuses.create',
            'statuses.read',
            'statuses.update',
            'statuses.delete',

            // Source Abilities
            'sources.create',
            'sources.read',
            'sources.update',
            'sources.delete',
        ]);
    }

    public function down(): void
    {
        $this->migrator->delete('api.enabled');
        $this->migrator->delete('api.token');
        $this->migrator->delete('api.token_generated_at');
        $this->migrator->delete('api.last_used_at');
        $this->migrator->delete('api.abilities');
    }
};

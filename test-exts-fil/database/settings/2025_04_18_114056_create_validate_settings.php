<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('whats-mark.wm_validate')) {
            $this->migrator->add('whats-mark.wm_validate', false);
        }

        if (! $this->migrator->exists('whats-mark.whatsmark_latest_version')) {
            $this->migrator->add('whats-mark.whatsmark_latest_version', '1.0.0');
        }
    }

    public function down(): void {}
};

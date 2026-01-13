<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('cron-job.last_cron_run')) {
            $this->migrator->add('cron-job.last_cron_run', false);
        }

    }

    public function down(): void
    {
        if ($this->migrator->exists('cron-job.last_cron_run')) {
            $this->migrator->delete('cron-job.last_cron_run');
        }
    }
};

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $settings = [
        'cron-job.status'              => 'unknown',
        'cron-job.last_cron_stats'     => '{}',
        'cron-job.last_execution_time' => 0,
        'cron-job.job_start_time'      => 0,
    ];

    public function up(): void
    {
        foreach ($this->settings as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->settings as $key => $value) {
            if ($this->migrator->exists($key)) {
                $this->migrator->delete($key);
            }
        }
    }
};

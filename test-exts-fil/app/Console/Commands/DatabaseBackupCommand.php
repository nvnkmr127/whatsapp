<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use App\Settings\DatabaseBackupSettings;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Create database backup based on settings';

    public function handle(DatabaseBackupService $backupService, DatabaseBackupSettings $settings)
    {
        // update last cron run value in db
        set_setting('cron-job.last_cron_run', now()->timestamp);

        if (! $settings->backup_enabled) {
            $this->info('Automated backups are disabled.');

            return 0;
        }

        $now        = Carbon::now();
        $backupTime = Carbon::createFromFormat('H:i', $settings->backup_time);
        $lastBackup = $settings->last_backup_at ? Carbon::parse($settings->last_backup_at) : null;

        // Check if it's time to run the backup based on frequency
        $shouldBackup = match ($settings->backup_frequency) {
            'daily'   => ! $lastBackup || $lastBackup->diffInDays($now)   >= 1,
            'weekly'  => ! $lastBackup || $lastBackup->diffInWeeks($now)  >= 1,
            'monthly' => ! $lastBackup || $lastBackup->diffInMonths($now) >= 1,
            default   => false,
        };

        if ($shouldBackup && $now->format('H:i') === $settings->backup_time) {
            $this->info('Creating backup...');

            if ($backupService->createBackup()) {
                $this->info('Backup created successfully!');

                return 0;
            }

            $this->error('Backup failed!');

            return 1;
        }

        $this->info('No backup needed at this time.');

        return 0;
    }
}

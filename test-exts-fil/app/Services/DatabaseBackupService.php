<?php

namespace App\Services;

use App\Settings\DatabaseBackupSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Databases\MySql;

class DatabaseBackupService
{
    protected DatabaseBackupSettings $settings;

    public function __construct(DatabaseBackupSettings $settings)
    {
        $this->settings = $settings;
    }

    public function createBackup(): bool
    {
        try {
            $filename = 'backup-' . Carbon::now()->format('Y-m-d-H-i-s') . '.sql';
            $path     = Storage::disk('backups')->path($filename);

            MySql::create()
                ->setDbName(config('database.connections.mysql.database'))
                ->setUserName(config('database.connections.mysql.username'))
                ->setPassword(config('database.connections.mysql.password'))
                ->setHost(config('database.connections.mysql.host'))
                ->dumpToFile($path);

            // Update last backup time
            $this->settings->last_backup_at = now();
            $this->settings->save();

            // Cleanup old backups
            $this->cleanupOldBackups();

            return true;
        } catch (\Exception $e) {
            app_log(t('backup_failed') . ' ' . $e->getMessage(), 'error');

            return false;
        }
    }

    public function getBackups(): array
    {
        $files   = Storage::disk('backups')->files();
        $backups = [];

        foreach ($files as $file) {
            if (str_ends_with($file, '.sql')) {
                $backups[] = [
                    'filename'   => $file,
                    'size'       => Storage::disk('backups')->size($file),
                    'created_at' => Storage::disk('backups')->lastModified($file),
                ];
            }
        }

        return array_reverse($backups);
    }

    public function cleanupOldBackups(): void
    {
        $backups = collect($this->getBackups())
            ->sortByDesc('created_at');

        if ($backups->count() > $this->settings->retention_count) {
            $backupsToDelete = $backups->slice($this->settings->retention_count);

            foreach ($backupsToDelete as $backup) {
                Storage::disk('backups')->delete($backup['filename']);
            }
        }
    }

    public function deleteBackup(string $filename): bool
    {
        try {
            return Storage::disk('backups')->delete($filename);
        } catch (\Exception $e) {
            app_log(t('failed_to_delete_backup') . ' ' . $e->getMessage(), 'error');

            return false;
        }
    }

    public function downloadBackup(string $filename): string
    {
        return Storage::disk('backups')->path($filename);
    }
}

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateDatabaseBackupSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('database-backup.backup_frequency', 'daily');
        $this->migrator->add('database-backup.retention_count', 5);
        $this->migrator->add('database-backup.backup_time', '00:00');
        $this->migrator->add('database-backup.last_backup_at', null);
        $this->migrator->add('database-backup.backup_enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('database-backup.backup_frequency');
        $this->migrator->delete('database-backup.retention_count');
        $this->migrator->delete('database-backup.backup_time');
        $this->migrator->delete('database-backup.last_backup_at');
        $this->migrator->delete('database-backup.backup_enabled');
    }
}

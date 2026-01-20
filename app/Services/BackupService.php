<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TenantBackup;
use App\Models\Integration;
use App\Services\Integrations\GoogleDriveService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ZipArchive;
use Exception;

class BackupService
{
    protected $tempPath;

    public function __construct()
    {
        $this->tempPath = storage_path('app/backup-temp');
        if (!file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }

    /**
     * Perform a backup for a specific tenant.
     */
    public function backupTenant(Team $team)
    {
        if (!$team->hasFeature('backups')) {
            throw new Exception("The backup feature is not available for your team. Please upgrade.");
        }

        $backupRecord = TenantBackup::create([
            'team_id' => $team->id,
            'type' => 'tenant',
            'filename' => "tenant_{$team->id}_" . now()->format('Y-m-d_H-i-s') . ".zip",
            'path' => "tenants/{$team->id}/",
            'status' => 'processing',
        ]);

        try {
            $zipPath = "{$this->tempPath}/{$backupRecord->filename}";
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Could not create ZIP file at {$zipPath}");
            }

            // 1. Export Database Data
            $sqlData = $this->getTenantSqlData($team->id);
            $zip->addFromString('database.sql', $sqlData);

            // 2. Export Storage Files
            $this->addTenantFilesToZip($team, $zip);

            $zip->close();

            // 3. Encrypt the ZIP file
            $encryptedPath = $this->encryptFile($zipPath);

            // 4. Update stats
            $backupRecord->update([
                'size' => filesize($encryptedPath),
                'checksum' => hash_file('sha256', $encryptedPath),
            ]);

            // 5. Dispatch to destinations
            $this->dispatchBackup($team, $encryptedPath, "tenants/{$team->id}/" . basename($encryptedPath));

            $backupRecord->update([
                'status' => 'completed',
                'filename' => basename($encryptedPath),
            ]);

            unlink($zipPath);
            unlink($encryptedPath);

            return $backupRecord;
        } catch (Exception $e) {
            $backupRecord->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Perform a global backup of the entire system.
     */
    public function backupGlobal()
    {
        $backupRecord = TenantBackup::create([
            'type' => 'global',
            'filename' => "global_" . now()->format('Y-m-d_H-i-s') . ".zip",
            'path' => "global/",
            'status' => 'processing',
        ]);

        try {
            $zipPath = "{$this->tempPath}/{$backupRecord->filename}";
            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $sqlData = $this->getFullDatabaseSql();
            $zip->addFromString('full_database.sql', $sqlData);

            $this->addAllFilesToZip($zip);
            $zip->close();

            $encryptedPath = $this->encryptFile($zipPath);

            $backupRecord->update([
                'size' => filesize($encryptedPath),
                'checksum' => hash_file('sha256', $encryptedPath),
            ]);

            $this->dispatchBackup(null, $encryptedPath, "global/" . basename($encryptedPath));

            $backupRecord->update([
                'status' => 'completed',
                'filename' => basename($encryptedPath),
            ]);

            unlink($zipPath);
            unlink($encryptedPath);

            return $backupRecord;
        } catch (Exception $e) {
            $backupRecord->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all tables that have a team_id column and export their data.
     */
    protected function getTenantSqlData($teamId)
    {
        $tables = Schema::getTables();

        $output = "-- Backup for Team ID: {$teamId}\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table['name'];

            if (Schema::hasColumn($tableName, 'team_id')) {
                $rows = DB::table($tableName)->where('team_id', $teamId)->get();
                if ($rows->count() > 0) {
                    $output .= "-- Table: {$tableName}\n";
                    foreach ($rows as $row) {
                        $values = array_map(function ($value) {
                            if (is_null($value))
                                return 'NULL';
                            return DB::getPdo()->quote($value);
                        }, (array) $row);

                        $output .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $output .= "\n";
                }
            }
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $output;
    }

    protected function getFullDatabaseSql()
    {
        // Simple PHP-based dump for small-medium DBs. 
        // For very large DBs, system calls to mysqldump are preferred.
        $tables = Schema::getTables();

        $output = "-- Full Global Backup\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table['name'];
            $rows = DB::table($tableName)->get();

            $output .= "-- Table: {$tableName}\n";
            foreach ($rows as $row) {
                $values = array_map(function ($value) {
                    if (is_null($value))
                        return 'NULL';
                    return DB::getPdo()->quote($value);
                }, (array) $row);

                $output .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $output;
    }

    protected function addTenantFilesToZip(Team $team, ZipArchive $zip)
    {
        // Add team-specific files (e.g., logo)
        if ($team->logo_path && Storage::disk('public')->exists($team->logo_path)) {
            $zip->addFile(Storage::disk('public')->path($team->logo_path), 'storage/public/' . $team->logo_path);
        }

        // Logic to add other team-specific assets if they existed in specific folders
        // Currently, we'll scan the public storage for anything that might be team-related
        // In a strictly architected SaaS, we'd have storage/app/public/tenants/{id}/
    }

    protected function addAllFilesToZip(ZipArchive $zip)
    {
        $files = Storage::disk('public')->allFiles();
        foreach ($files as $file) {
            $zip->addFile(Storage::disk('public')->path($file), 'storage/public/' . $file);
        }
    }

    protected function dispatchBackup(?Team $team, $sourcePath, $destinationPath)
    {
        $content = file_get_contents($sourcePath);

        // Local Storage
        Storage::disk('local')->put("backups/{$destinationPath}", $content);

        // Google Drive integration for the specific tenant
        if ($team) {
            if (!$team->hasFeature('cloud_backups')) {
                logger()->info("Cloud backup skipped for Team {$team->id}: plan restriction.");
                return;
            }

            $gdIntegration = Integration::where('team_id', $team->id)
                ->where('type', 'google_drive')
                ->where('status', 'active')
                ->first();

            if ($gdIntegration) {
                try {
                    $gdService = new GoogleDriveService($gdIntegration);
                    $gdService->uploadFile($sourcePath, basename($destinationPath));
                } catch (Exception $e) {
                    logger()->error("Backup upload to Google Drive failed for Team {$team->id}: " . $e->getMessage());

                    $gdIntegration->update([
                        'error_message' => $e->getMessage()
                    ]);
                }
            }
        }

        // Global Google Drive (Optional check if configured in filesystems)
        if (config('filesystems.disks.google_drive')) {
            try {
                Storage::disk('google_drive')->put($destinationPath, $content);
            } catch (Exception $e) {
                logger()->error("Backup upload to Global Google Drive failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Enforce 7-day rolling retention.
     */
    public function cleanOldBackups()
    {
        $threshold = now()->subDays(7);
        $backups = TenantBackup::where('created_at', '<', $threshold)
            ->where('status', '!=', 'pruned')
            ->get();

        foreach ($backups as $backup) {
            $fullPath = "backups/{$backup->path}{$backup->filename}";

            // Delete from Local
            if (Storage::disk('local')->exists($fullPath)) {
                Storage::disk('local')->delete($fullPath);
            }

            // Delete from Google Drive if configured
            if (config('filesystems.disks.google_drive')) {
                $remotePath = "{$backup->path}{$backup->filename}";
                try {
                    if (Storage::disk('google_drive')->exists($remotePath)) {
                        Storage::disk('google_drive')->delete($remotePath);
                    }
                } catch (Exception $e) {
                }
            }

            $backup->update([
                'status' => 'pruned',
                'pruned_at' => now(),
            ]);
        }
    }

    /**
     * Encrypt a file using AES-256-CBC.
     */
    protected function encryptFile($filePath)
    {
        $key = config('app.key');
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $content = file_get_contents($filePath);
        $encryptedContent = openssl_encrypt($content, 'aes-256-cbc', $key, 0, $iv);

        $encryptedPath = $filePath . '.enc';
        // Prepend IV to the file for decryption later
        file_put_contents($encryptedPath, $iv . $encryptedContent);

        return $encryptedPath;
    }

    /**
     * Decrypt a file (for manual restoration if needed).
     */
    public function decryptFile($encryptedPath, $outputPath)
    {
        $key = config('app.key');
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $data = file_get_contents($encryptedPath);
        $ivSize = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivSize);
        $encryptedContent = substr($data, $ivSize);

        $decryptedContent = openssl_decrypt($encryptedContent, 'aes-256-cbc', $key, 0, $iv);
        file_put_contents($outputPath, $decryptedContent);
    }

    /**
     * Restore a tenant's data from a backup.
     */
    public function restoreTenant(Team $team, TenantBackup $backup)
    {
        if ($backup->team_id !== $team->id) {
            throw new Exception("Backup does not belong to this team.");
        }

        // 1. Create Pre-Restore Snapshot (Emergency Rollback)
        $this->backupTenant($team);

        try {
            DB::beginTransaction();

            // 2. Locate and Prepare the File
            $encryptedPath = $this->prepareBackupFile($backup);
            $zipPath = str_replace('.enc', '', $encryptedPath);
            $this->decryptFile($encryptedPath, $zipPath);

            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new Exception("Could not open backup ZIP.");
            }

            // 3. Restore Database
            $sql = $zip->getFromName('database.sql');
            if ($sql) {
                // Clear existing tenant data
                $this->clearTenantData($team->id);
                // Execute restoration SQL
                $this->executeSql($sql);
            }

            // 4. Restore Assets
            // Logic to extract files and place them back in storage/app/public/

            $zip->close();
            unlink($zipPath);
            if (file_exists($encryptedPath))
                unlink($encryptedPath);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore the entire system from a global backup.
     */
    public function restoreGlobal(TenantBackup $backup)
    {
        if ($backup->type !== 'global') {
            throw new Exception("Cannot perform global restore from a tenant backup.");
        }

        // 1. Global Snapshot
        $this->backupGlobal();

        try {
            $encryptedPath = $this->prepareBackupFile($backup);
            $zipPath = str_replace('.enc', '', $encryptedPath);
            $this->decryptFile($encryptedPath, $zipPath);

            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new Exception("Could not open backup ZIP.");
            }

            $sql = $zip->getFromName('full_database.sql');
            if ($sql) {
                // WARNING: Highly destructive. Drops and re-creates or wipes tables.
                $this->executeSql($sql);
            }

            $zip->close();
            unlink($zipPath);
            if (file_exists($encryptedPath))
                unlink($encryptedPath);

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function prepareBackupFile(TenantBackup $backup)
    {
        $filename = $backup->filename;
        $localPath = "backups/{$backup->path}{$filename}";
        $tempDest = "{$this->tempPath}/{$filename}";

        if (Storage::disk('local')->exists($localPath)) {
            file_put_contents($tempDest, Storage::disk('local')->get($localPath));
        } else {
            // Attempt to download from Google Drive
            $this->downloadFromGoogleDrive($backup, $tempDest);
        }

        // Integrity Check
        if (hash_file('sha256', $tempDest) !== $backup->checksum) {
            unlink($tempDest);
            throw new Exception("Backup integrity check failed. Checksum mismatch.");
        }

        return $tempDest;
    }

    protected function downloadFromGoogleDrive(TenantBackup $backup, $destPath)
    {
        if ($backup->type === 'tenant') {
            $gdIntegration = Integration::where('team_id', $backup->team_id)
                ->where('type', 'google_drive')
                ->where('status', 'active')
                ->first();

            if ($gdIntegration) {
                // Logic to download from Google Drive would go here using GoogleDriveService
                throw new Exception("Cloud restore not fully implemented yet.");
            }
        }

        throw new Exception("Backup file not found locally or in cloud.");
    }

    protected function clearTenantData($teamId)
    {
        $tables = Schema::getTables();
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        foreach ($tables as $table) {
            $tableName = $table['name'];
            if (Schema::hasColumn($tableName, 'team_id')) {
                DB::table($tableName)->where('team_id', $teamId)->delete();
            }
        }

        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    protected function executeSql($sql)
    {
        DB::unprepared($sql);
    }
}

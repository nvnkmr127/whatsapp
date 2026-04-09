<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Integration;
use App\Models\Team;
use App\Models\TenantBackup;
use App\Services\Integrations\GoogleDriveService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BackupService
{
    protected $tempPath;

    public function __construct()
    {
        $this->tempPath = storage_path('app/backup-temp');
        if (! file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }

    /**
     * Perform a backup for a specific tenant.
     *
     * RACE CONDITION PROTECTION:
     * Acquires a per-team Cache::lock() mutex before starting. Enables tenant
     * maintenance mode to signal jobs to pause processing. Both are released
     * in the finally block regardless of success or failure.
     *
     * @throws BackupAlreadyRunningException if a backup/restore is already running.
     */
    /**
     * Entry point to trigger a backup.
     * High-level checks, record creation, and async dispatch.
     */
    public function backupTenant(Team $team)
    {
        $entitlement = $team->entitlement();
        if (! $entitlement->hasFeature('backups')) {
            throw new \App\Exceptions\Backup\BackupException('The backup feature is not available for your team. Please upgrade.');
        }

        // 1. Check Max Backups Count
        if (! $entitlement->withinLimit('max_backups_per_team')) {
            $limit = $entitlement->limit('max_backups_per_team');
            throw new \App\Exceptions\Backup\BackupException("Backup limit reached. You can only keep up to {$limit} backups. Please delete old backups to create a new one.");
        }

        // 2. Check Max Storage Size
        if (! $entitlement->withinLimit('max_storage_mb')) {
            $limit = $entitlement->limit('max_storage_mb');
            throw new Exception("Storage limit reached. You can only use up to {$limit} MB for backups. Please delete old backups to free up space.");
        }

        // 3. Check Cooldown
        $cooldownHours = $entitlement->limit('cooldown_hours_between_backups');
        if ($cooldownHours > 0) {
            $lastBackupAt = $entitlement->usage['last_backup_at'] ?? null;
            if ($lastBackupAt) {
                $nextAllowedAt = \Carbon\Carbon::parse($lastBackupAt)->addHours($cooldownHours);
                if (now()->lessThan($nextAllowedAt)) {
                    $waitTime = now()->diffForHumans($nextAllowedAt, true);
                    throw new Exception("Backup on cooldown. Please wait {$waitTime} before creating another backup.");
                }
            }
        }

        $backupRecord = TenantBackup::create([
            'team_id' => $team->id,
            'type' => 'tenant',
            'filename' => "tenant_{$team->id}_".now()->format('Y-m-d_H-i-s').'.zip',
            'path' => "tenants/{$team->id}/",
            'status' => 'pending',
        ]);

        \App\Jobs\ProcessTenantBackupJob::dispatch($backupRecord);

        return $backupRecord;
    }

    /**
     * Step 1: Generate the backup file (DB + Files).
     * Runs inside ProcessTenantBackupJob.
     */
    public function generateBackup(TenantBackup $backupRecord)
    {
        $team = $backupRecord->team;

        /** @var BackupLockService $lockService */
        $lockService = app(BackupLockService::class);
        $lock = $lockService->acquireBackupLock($team);

        try {
            $backupRecord->update(['status' => 'processing']);

            $zipPath = "{$this->tempPath}/{$backupRecord->filename}";
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \App\Exceptions\Backup\BackupException("Could not create ZIP file at {$zipPath}");
            }

            // 1. Export Database Data
            $sqlData = $this->getTenantSqlData($team->id);
            $zip->addFromString('database.sql', $sqlData);

            // 2. Add System Signature (CVE-13 Malware Risk Protection)
            // Signs the primary payload to ensure it wasn't tampered with inside the ZIP.
            $innerSignature = hash_hmac('sha256', $sqlData, config('app.key'));
            $zip->addFromString('backup.signature', $innerSignature);

            // 3. Export Storage Files
            $this->addTenantFilesToZip($team, $zip);

            $zip->close();

            // 3. Encrypt the ZIP file using a per-team key
            $encryptedPath = $this->encryptFile($zipPath, $team);
            $finalFilename = basename($encryptedPath);

            // 4. Update stats
            $checksum = hash_file('sha256', $encryptedPath);
            $backupRecord->update([
                'size' => filesize($encryptedPath),
                'checksum' => $checksum,
                'signature' => hash_hmac('sha256', $checksum, config('app.key')),
                'filename' => $finalFilename,
            ]);

            unlink($zipPath);

            // 5. Move to local storage baseline
            $content = file_get_contents($encryptedPath);
            Storage::disk('local')->put("backups/{$backupRecord->path}{$finalFilename}", $content);

            // 6. Dispatch Upload Job
            \App\Jobs\UploadTenantBackupJob::dispatch($backupRecord, $encryptedPath);

            return $backupRecord;
        } catch (Exception $e) {
            $backupRecord->update([
                'status' => 'failed',
                'error_message' => 'Generation failed: '.$e->getMessage(),
            ]);
            throw $e;
        } finally {
            $lockService->release($lock, $team);
        }
    }

    /**
     * Step 2: Upload to Cloud (Google Drive).
     * Runs inside UploadTenantBackupJob.
     */
    public function uploadBackup(TenantBackup $backupRecord, string $localFilePath)
    {
        if (! file_exists($localFilePath)) {
            // If the local file is gone (e.g. server restart), we try to get it from local storage
            $storedPath = "backups/{$backupRecord->path}{$backupRecord->filename}";
            if (Storage::disk('local')->exists($storedPath)) {
                $content = Storage::disk('local')->get($storedPath);
                file_put_contents($localFilePath, $content);
            } else {
                $backupRecord->update(['status' => 'failed', 'error_message' => 'Local backup file missing for upload.']);

                return;
            }
        }

        $backupRecord->update(['status' => 'processing']);

        try {
            if ($backupRecord->type === 'global') {
                // Global Backup: Upload to Global Google Drive Disk (if configured)
                if (config('filesystems.disks.google_drive')) {
                    $destinationPath = "{$backupRecord->path}{$backupRecord->filename}";
                    $content = file_get_contents($localFilePath);
                    Storage::disk('google_drive')->put($destinationPath, $content);
                    $backupRecord->update(['status' => 'completed']);
                } else {
                    $backupRecord->update(['status' => 'completed']);
                }
            } else {
                // Tenant Backup
                $team = $backupRecord->team;
                if ($team->hasFeature('cloud_backups')) {
                    $gdIntegration = Integration::where('team_id', $team->id)
                        ->where('type', 'google_drive')
                        ->first();

                    if ($gdIntegration) {
                        if ($gdIntegration->status === 'error') {
                            throw new \App\Exceptions\Backup\BackupException('Google Drive connection is inactive.');
                        }

                        $gdService = new GoogleDriveService($gdIntegration);
                        $fileId = $gdService->uploadFile($localFilePath, $backupRecord->filename);

                        $backupRecord->update([
                            'remote_account_id' => $gdService->getAccountIdentifier(),
                            'remote_file_id' => $fileId,
                            'status' => 'completed',
                        ]);
                    } elseif (config('filesystems.disks.google_drive')) {
                        // Model B: Platform Central Drive Fallback
                        $destinationPath = "{$backupRecord->path}{$backupRecord->filename}";
                        $content = file_get_contents($localFilePath);
                        Storage::disk('google_drive')->put($destinationPath, $content);

                        $backupRecord->update([
                            'disk' => 'google_drive',
                            'status' => 'completed',
                        ]);
                    } else {
                        $backupRecord->update(['status' => 'completed']);
                    }
                } else {
                    $backupRecord->update(['status' => 'completed']);
                }
            }

            // Step 3: Verification
            $this->verifyBackup($backupRecord, $localFilePath);

            // Cleanup local temp file
            if (file_exists($localFilePath)) {
                unlink($localFilePath);
            }

        } catch (Exception $e) {
            $backupRecord->update([
                'status' => 'failed',
                'error_message' => 'Upload failed: '.$e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Step 3: Verify Integrity.
     */
    protected function verifyBackup(TenantBackup $backupRecord, string $localFilePath)
    {
        // Simple verification: check if record has checksum and size
        if ($backupRecord->checksum && $backupRecord->size > 0) {
            $backupRecord->update(['status' => 'completed']);
        } else {
            $backupRecord->update(['status' => 'failed', 'error_message' => 'Integrity verification failed.']);
        }
    }

    /**
     * Perform a global backup of the entire system.
     */
    public function backupGlobal()
    {
        $backupRecord = TenantBackup::create([
            'type' => 'global',
            'filename' => 'global_'.now()->format('Y-m-d_H-i-s').'.zip',
            'path' => 'global/',
            'status' => 'pending',
        ]);

        \App\Jobs\ProcessTenantBackupJob::dispatch($backupRecord);

        return $backupRecord;
    }

    /**
     * Implementation for global generation.
     */
    public function generateGlobalBackup(TenantBackup $backupRecord)
    {
        try {
            $backupRecord->update(['status' => 'generating']);

            $zipPath = "{$this->tempPath}/{$backupRecord->filename}";
            $zip = new ZipArchive;
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $sqlData = $this->getFullDatabaseSql();
            $zip->addFromString('full_database.sql', $sqlData);

            // 2. Add System Signature
            $innerSignature = hash_hmac('sha256', $sqlData, config('app.key'));
            $zip->addFromString('backup.signature', $innerSignature);

            $this->addAllFilesToZip($zip);
            $zip->close();

            $encryptedPath = $this->encryptFile($zipPath, null);
            $finalFilename = basename($encryptedPath);

            $checksum = hash_file('sha256', $encryptedPath);
            $backupRecord->update([
                'size' => filesize($encryptedPath),
                'checksum' => $checksum,
                'signature' => hash_hmac('sha256', $checksum, config('app.key')),
                'filename' => $finalFilename,
            ]);

            unlink($zipPath);

            // Move to local storage baseline
            $content = file_get_contents($encryptedPath);
            Storage::disk('local')->put("backups/{$backupRecord->path}{$finalFilename}", $content);

            // Dispatch Upload Job (Global version)
            \App\Jobs\UploadTenantBackupJob::dispatch($backupRecord, $encryptedPath);

            return $backupRecord;
        } catch (Exception $e) {
            $backupRecord->update([
                'status' => 'failed',
                'error_message' => 'Global generation failed: '.$e->getMessage(),
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

        // ─────────────────────────────────────────────────────────────────────
        // IMMUTABLE LEDGER TABLES — must NEVER be backed up or restored.
        // Restoring any of these would allow financial fraud, compliance breaches,
        // or audit tampering. Table names match actual DB schema (Laravel convention).
        // ─────────────────────────────────────────────────────────────────────
        $protectedTables = [
            'team_wallets',           // Wallet balance — must never be reverted
            'team_transactions',      // Append-only financial ledger — immutable
            'billing_overrides',      // Super Admin overrides — not tenant-scoped
            'whatsapp_conversations', // Meta billing windows — financial records
            'audit_logs',             // Compliance audit trail — immutable
            'activity_logs',          // System activity log — immutable
            'system_settings',        // Global config — not tenant data
            'personal_access_tokens', // API tokens — must not be blindly restored
            'consent_registry',       // GDPR — newer opt-outs must never be overridden
            'opt_outs',               // Compliance — STOP users must stay stopped
        ];

        $output = "-- Backup for Team ID: {$teamId}\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table['name'];

            if (in_array($tableName, $protectedTables)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'team_id')) {
                $rows = DB::table($tableName)->where('team_id', $teamId)->get();
                if ($rows->count() > 0) {
                    $output .= "-- Table: {$tableName}\n";
                    foreach ($rows as $row) {
                        $values = array_map(function ($value) {
                            if (is_null($value)) {
                                return 'NULL';
                            }

                            return DB::getPdo()->quote($value);
                        }, (array) $row);

                        $output .= "INSERT INTO `{$tableName}` VALUES (".implode(', ', $values).");\n";
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
                    if (is_null($value)) {
                        return 'NULL';
                    }

                    return DB::getPdo()->quote($value);
                }, (array) $row);

                $output .= "INSERT INTO `{$tableName}` VALUES (".implode(', ', $values).");\n";
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
            $zip->addFile(Storage::disk('public')->path($team->logo_path), 'storage/public/'.$team->logo_path);
        }

        // Logic to add other team-specific assets if they existed in specific folders
        // Currently, we'll scan the public storage for anything that might be team-related
        // In a strictly architected SaaS, we'd have storage/app/public/tenants/{id}/
    }

    protected function addAllFilesToZip(ZipArchive $zip)
    {
        $files = Storage::disk('public')->allFiles();
        foreach ($files as $file) {
            $zip->addFile(Storage::disk('public')->path($file), 'storage/public/'.$file);
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
            /** @var TenantBackup $backup */
            $fullPath = "backups/{$backup->path}{$backup->filename}";

            // 1. Delete from Local Storage
            if (Storage::disk('local')->exists($fullPath)) {
                Storage::disk('local')->delete($fullPath);
            }

            // 2. Delete from Google Drive (Integration or Global)
            if ($backup->type === 'tenant') {
                $gdIntegration = Integration::where('team_id', $backup->team_id)
                    ->where('type', 'google_drive')
                    ->where('status', 'active')
                    ->first();

                if ($gdIntegration) {
                    try {
                        $gdService = new GoogleDriveService($gdIntegration);
                        $fileId = $backup->remote_file_id;

                        if (! $fileId) {
                            $file = $gdService->findFileByName($backup->filename);
                            $fileId = $file['id'] ?? null;
                        }

                        if ($fileId) {
                            $gdService->deleteFile($fileId);
                        }
                    } catch (Exception $e) {
                        Log::warning("Failed to delete old tenant backup from Google Drive for Team {$backup->team_id}: ".$e->getMessage());
                    }
                }
            } elseif ($backup->type === 'global' && config('filesystems.disks.google_drive')) {
                $remotePath = "{$backup->path}{$backup->filename}";
                try {
                    // Security: Explicit path check before global disk deletion
                    if (str_starts_with($remotePath, 'global/') && Storage::disk('google_drive')->exists($remotePath)) {
                        Storage::disk('google_drive')->delete($remotePath);
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to delete old global backup from Google Drive: '.$e->getMessage());
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
     *
     * SECURITY: Use a per-team derived key if a team is provided.
     * This ensures that even if a backup is leaked, it cannot be decrypted
     * without the specific team's derived key context.
     */
    protected function encryptFile($filePath, ?Team $team)
    {
        $key = $this->deriveKey($team);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        $content = file_get_contents($filePath);
        $encryptedContent = openssl_encrypt($content, 'aes-256-cbc', $key, 0, $iv);

        $encryptedPath = $filePath.'.enc';
        // Prepend IV to the file for decryption later
        file_put_contents($encryptedPath, $iv.$encryptedContent);

        return $encryptedPath;
    }

    /**
     * Decrypt a file using AES-256-CBC.
     *
     * Supports per-team keys with a fallback to the global system key
     * for legacy backups taken before per-team encryption was enabled.
     */
    public function decryptFile($encryptedPath, $outputPath, ?Team $team = null)
    {
        $data = file_get_contents($encryptedPath);
        $ivSize = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivSize);
        $cipher = substr($data, $ivSize);

        // Try primary key (per-team if provided)
        $primaryKey = $this->deriveKey($team);
        $decrypted = openssl_decrypt($cipher, 'aes-256-cbc', $primaryKey, 0, $iv);

        // Fallback: If decryption failed and we were using a team key, try the global app key
        if ($decrypted === false && $team !== null) {
            $globalKey = $this->deriveKey(null);
            $decrypted = openssl_decrypt($cipher, 'aes-256-cbc', $globalKey, 0, $iv);
        }

        if ($decrypted === false) {
            throw new \App\Exceptions\Backup\BackupException('Decryption failed. Invalid key or corrupted backup file.');
        }

        file_put_contents($outputPath, $decrypted);
    }

    /**
     * Derive an encryption key.
     * If team is null: returns the standard Laravel app key.
     * If team is set: returns HMAC-SHA256(team_id, app_key).
     */
    protected function deriveKey(?Team $team): string
    {
        $baseKey = config('app.key');
        if (Str::startsWith($baseKey, 'base64:')) {
            $baseKey = base64_decode(substr($baseKey, 7));
        }

        if (! $team) {
            return $baseKey;
        }

        // Deterministic per-team key bound to the system secret
        return hash_hmac('sha256', (string) $team->id, $baseKey, true);
    }

    /**
     * Restore a tenant's data from a backup.
     *
     * CONSENT SAFETY:
     * After the SQL restore, the contacts table may contain stale opt_in_status
     * values from the backup snapshot (e.g., 'opted_in' for a contact who sent
     * STOP *after* the backup was taken). The final mandatory step re-queries
     * the immutable consent_registry and re-applies any authoritative opt-outs,
     * preventing illegal messaging of opted-out users. This is the GDPR rollback
     * risk mitigation required by WhatsApp Business Policy and GDPR Article 7.
     */
    public function restoreTenant(Team $team, TenantBackup $backup)
    {
        if ($backup->team_id !== $team->id) {
            throw new \App\Exceptions\Backup\RestoreException('Backup does not belong to this team.');
        }

        /** @var BackupLockService $lockService */
        $lockService = app(BackupLockService::class);

        // 1. Put tenant in maintenance mode
        $lock = $lockService->acquireRestoreLock($team);

        try {
            // 2. Pause queue & 3. Clear cache
            // We run a pre-reset to ensure a clean slate before DB wipes.
            $this->logBackupActivity($team, 'backup.restore_pre_reset', 'Pre-restore state reset (queue pause & cache clear).');
            $this->postRestoreReset($team);

            // Emergency snapshot before we overwrite anything
            $this->backupTenant($team);

            try {
                DB::beginTransaction();

                // 4. Restore
                $encryptedPath = $this->prepareBackupFile($backup);
                $this->performRestore($team, $encryptedPath, "Database restore from tenant backup [{$backup->id}]");

                DB::commit();

                // 5. Rebuild indexes
                $this->rebuildIndexes($team);

                // Final post-reset to clear any state imported from the backup
                $this->postRestoreReset($team);

                return true;
            } catch (Exception $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                throw $e;
            }
        } finally {
            // 6. Resume
            $lockService->release($lock, $team);
        }
    }

    /**
     * Restore a tenant's data from a manually uploaded file.
     *
     * SECURITY:
     * decryptFile() will be called with the current team's context.
     * If the user uploads another team's backup, it WILL NOT DECRYPT.
     * This provides a strong cryptographic air-gap even for manual uploads.
     */
    public function restoreManual(Team $team, string $filePath)
    {
        /** @var BackupLockService $lockService */
        $lockService = app(BackupLockService::class);

        // 1. Put tenant in maintenance mode
        $lock = $lockService->acquireRestoreLock($team);

        try {
            // 2. Pause queue & 3. Clear cache
            $this->postRestoreReset($team);

            // Emergency snapshot
            $this->backupTenant($team);

            // 1. Mime Type Check
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($filePath);
            if (in_array($mime, ['text/x-sql', 'text/x-php', 'application/x-httpd-php'])) {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                throw new \App\Exceptions\Backup\RestoreException("MALWARE RISK: The uploaded file has an invalid or malicious mime type ({$mime}).");
            }

            try {
                DB::beginTransaction();

                // 4. Restore
                $this->performRestore($team, $filePath, 'Manual database restore from upload');

                DB::commit();

                // 5. Rebuild indexes
                $this->rebuildIndexes($team);

                // Final reset
                $this->postRestoreReset($team);

                // Cleanup manual file
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                return true;
            } catch (Exception $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                throw $e;
            }
        } finally {
            // 6. Resume
            $lockService->release($lock, $team);
        }
    }

    /**
     * Core restore logic (Internal).
     * Decrypts, extracts, clears data, executes SQL, and rehydrates.
     */
    protected function performRestore(Team $team, string $encryptedPath, string $contextLabel)
    {
        $zipPath = str_replace('.enc', '', $encryptedPath);
        $this->decryptFile($encryptedPath, $zipPath, $team);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new Exception('Could not open backup ZIP.');
        }

        // 3. Restore Database
        $sql = $zip->getFromName($team ? 'database.sql' : 'full_database.sql');
        if ($sql) {
            // CVE-13: Validate Internal Signature
            $innerSignature = $zip->getFromName('backup.signature');
            if (! $innerSignature || hash_hmac('sha256', $sql, config('app.key')) !== $innerSignature) {
                $zip->close();
                throw new \App\Exceptions\Backup\RestoreException('MALWARE RISK: The internal backup signature is missing or invalid. Restoration aborted.');
            }

            // Clear existing tenant data (consent_registry is EXCLUDED)
            if ($team) {
                $this->clearTenantData($team->id);
            }
            // Execute restoration SQL
            $this->executeSql($sql);
        }

        // 4. Restore Assets
        // (Placeholder for assets extraction to storage/app/public/tenants/{id}/)

        $zip->close();
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
        if (file_exists($encryptedPath)) {
            unlink($encryptedPath);
        }

        // 5. MANDATORY CONSENT REHYDRATION
        try {
            /** @var ConsentService $consentService */
            $consentService = app(ConsentService::class);
            $consentService->rehydrateTeamFromRegistry($team->id);

            Log::info("Backup restore consent rehydration complete: {$contextLabel}", [
                'team_id' => $team->id,
            ]);
        } catch (Exception $e) {
            Log::emergency("CRITICAL: ConsentRegistry rehydration failed after restore. GDPR risk! {$contextLabel}", [
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // 6. POST-RESTORE STATE RESET
        $this->postRestoreReset($team);
    }

    /**
     * Step 6 — Post-Restore State Reset.
     *
     * Executed after every tenant restore (outside the DB transaction) to
     * eliminate phantom operations caused by stale in-flight state.
     */
    protected function postRestoreReset(Team $team): void
    {
        try {
            $result = app(PostRestoreStateResetService::class)->reset($team);
            Log::info("[BackupService] Post-restore state reset complete for Team {$team->id}", $result['counters']);
        } catch (Exception $e) {
            // Log at ERROR but do not re-throw — the restore itself succeeded.
            Log::error("[BackupService] Post-restore state reset FAILED for Team {$team->id}: ".$e->getMessage(), [
                'team_id' => $team->id,
            ]);
        }
    }

    /**
     * Restore the entire system from a global backup.
     */
    public function restoreGlobal(TenantBackup $backup)
    {
        if ($backup->type !== 'global') {
            throw new Exception('Cannot perform global restore from a tenant backup.');
        }

        // 1. Global Snapshot
        $this->backupGlobal();

        try {
            $encryptedPath = $this->prepareBackupFile($backup);
            $zipPath = str_replace('.enc', '', $encryptedPath);
            $this->decryptFile($encryptedPath, $zipPath, null);

            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new Exception('Could not open backup ZIP.');
            }

            $sql = $zip->getFromName('full_database.sql');
            if ($sql) {
                // WARNING: Highly destructive. Drops and re-creates or wipes tables.
                $this->executeSql($sql);
            }

            $zip->close();
            unlink($zipPath);
            if (file_exists($encryptedPath)) {
                unlink($encryptedPath);
            }

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

        try {
            if (Storage::disk('local')->exists($localPath)) {
                file_put_contents($tempDest, Storage::disk('local')->get($localPath));
            } else {
                // Attempt to download from Google Drive
                $this->downloadFromGoogleDrive($backup, $tempDest);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // CRITICAL: File was deleted manually in the cloud provider.
            // Mark the backup record as invalid and throw a user-friendly error.
            $backup->update([
                'status' => 'invalid',
                'error_message' => 'REMOTE_FILE_MISSING: The backup file was deleted from Google Drive manually.',
            ]);

            throw new Exception('Restoration failed: The cloud backup file no longer exists (404). The record has been marked as invalid.');
        }

        // 1. Mime Type Check
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tempDest);
        // Encrypted files are usually octet-stream, but we definitely want to reject plain .sql or .php
        if (in_array($mime, ['text/x-sql', 'text/x-php', 'application/x-httpd-php'])) {
            unlink($tempDest);
            throw new Exception("MALWARE RISK: The backup file has an invalid or malicious mime type ({$mime}).");
        }

        // 2. Integrity Check (Checksum)
        $actualChecksum = hash_file('sha256', $tempDest);
        if ($actualChecksum !== $backup->checksum) {
            if (file_exists($tempDest)) {
                unlink($tempDest);
            }

            $backup->update([
                'status' => 'invalid',
                'error_message' => 'CORRUPTED: The backup file checksum does not match.',
            ]);

            throw new Exception('Backup integrity check failed. The file has been modified or corrupted.');
        }

        // 3. System Source Verification (HMAC Signature)
        $expectedSignature = hash_hmac('sha256', $actualChecksum, config('app.key'));
        if ($backup->signature && $backup->signature !== $expectedSignature) {
            unlink($tempDest);
            throw new Exception('MALWARE RISK: System signature verification failed. This file was not generated by this system.');
        }

        return $tempDest;
    }

    protected function downloadFromGoogleDrive(TenantBackup $backup, string $destPath): void
    {
        // 1. Attempt Tenant Integration
        if ($backup->type === 'tenant') {
            $gdIntegration = Integration::where('team_id', $backup->team_id)
                ->where('type', 'google_drive')
                ->where('status', 'active')
                ->first();

            if ($gdIntegration) {
                try {
                    $gdService = new GoogleDriveService($gdIntegration);

                    // Security: Ownership verification (ACCOUNT LEVEL)
                    if ($backup->remote_account_id && $gdService->getAccountIdentifier() !== $backup->remote_account_id) {
                        throw new Exception(
                            'Restoration Blocked: This backup was created with a different Google account. '.
                            "Ownership verification failed for Team {$backup->team_id}."
                        );
                    }

                    $fileId = $backup->remote_file_id;

                    // Fallback to name-based lookup for legacy backups missing remote_file_id
                    if (! $fileId) {
                        $file = $gdService->findFileByName($backup->filename);
                        if (! $file) {
                            throw new Exception('Backup file not found in Google Drive.');
                        }
                        $fileId = $file['id'];
                    }

                    $gdService->downloadFile($fileId, $destPath);

                    return;
                } catch (Exception $e) {
                    Log::error("Failed to download from Google Drive Integration for Team {$backup->team_id}: ".$e->getMessage());
                    throw $e;
                }
            }
        }

        // 2. Fallback to Global Google Drive Disk (if configured)
        if (config('filesystems.disks.google_drive')) {
            $remotePath = "{$backup->path}{$backup->filename}";
            try {
                if (Storage::disk('google_drive')->exists($remotePath)) {
                    $content = Storage::disk('google_drive')->get($remotePath);
                    file_put_contents($destPath, $content);

                    return;
                }
            } catch (Exception $e) {
                Log::error('Failed to download from Global Google Drive: '.$e->getMessage());
            }
        }

        throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Backup file '{$backup->filename}' not found locally or in cloud.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Security & Logic Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Notify all team admins when the Drive integration breaks.
     */
    protected function notifyAdminsOfDriveFailure(Team $team, string $error): void
    {
        // Owner always gets notified
        $recipients = collect([$team->owner]);

        // Plus any user with 'admin' role in this team
        $admins = $team->users()
            ->wherePivot('role', 'admin')
            ->get();

        $recipients = $recipients->concat($admins)->unique('id')->filter();

        \Illuminate\Support\Facades\Notification::send(
            $recipients,
            new \App\Notifications\GoogleDriveTokenRevoked($team, $error)
        );
    }

    /**
     * Step 5 — Rebuild database indexes for the tenant's data.
     * Uses OPTIMIZE TABLE to defragment and rebuild indexes after mass deletion/insertion.
     */
    protected function rebuildIndexes(Team $team): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        try {
            $tables = Schema::getTables();
            foreach ($tables as $table) {
                $tableName = $table['name'];
                // Only optimize tables that have tenant data
                if (Schema::hasColumn($tableName, 'team_id')) {
                    // OPTIMIZE TABLE is non-blocking in modern MySQL (InnoDB) but can be slow
                    DB::statement("OPTIMIZE TABLE `{$tableName}`");
                }
            }
            Log::info("Backup restore: Index rebuild complete for Team {$team->id}");
        } catch (Exception $e) {
            Log::warning("Backup restore index rebuild failed for Team {$team->id}: ".$e->getMessage());
        }
    }

    /**
     * Record security and operational events to the activity log.
     */
    protected function logBackupActivity(Team $team, string $action, string $description): void
    {
        try {
            ActivityLog::create([
                'team_id' => $team->id,
                'action' => $action,
                'description' => $description,
                'properties' => [
                    'source' => 'BackupService',
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Backup ActivityLog failed: '.$e->getMessage());
        }
    }

    protected function clearTenantData($teamId)
    {
        $tables = Schema::getTables();
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        // ─────────────────────────────────────────────────────────────────────
        // IMMUTABLE LEDGER TABLES — must NEVER be wiped during restore.
        // These tables form an external ledger. Wiping them during restore
        // would effectively reset billing history, enabling financial fraud.
        // ─────────────────────────────────────────────────────────────────────
        $protectedTables = [
            'team_wallets',           // Wallet balance — must never be reverted
            'team_transactions',      // Append-only financial ledger — immutable
            'billing_overrides',      // Super Admin overrides — not tenant-scoped
            'whatsapp_conversations', // Meta billing windows — financial records
            'audit_logs',             // Compliance audit trail — immutable
            'activity_logs',          // System activity log — immutable
            'system_settings',        // Global config — not tenant data
            'personal_access_tokens', // API tokens — must not be blindly wiped
            'consent_registry',       // GDPR — newer opt-outs must never be overridden
            'opt_outs',               // Compliance — STOP users must stay stopped
        ];

        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        foreach ($tables as $table) {
            $tableName = $table['name'];

            if (in_array($tableName, $protectedTables)) {
                continue;
            }

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
        // WARNING: Executing raw SQL from backup files is dangerous if the file source is untrusted.
        // Ensure backups are stored securely and checksums are verified before restoration.
        try {
            DB::unprepared($sql);
        } catch (Exception $e) {
            Log::error('SQL Restore failed: '.$e->getMessage());
            throw $e;
        }
    }
}

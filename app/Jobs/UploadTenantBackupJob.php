<?php

namespace App\Jobs;

use App\Models\TenantBackup;
use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UploadTenantBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $backupRecord;
    protected $localFilePath;

    /**
     * Create a new job instance.
     */
    public function __construct(TenantBackup $backupRecord, string $localFilePath)
    {
        $this->backupRecord = $backupRecord;
        $this->localFilePath = $localFilePath;
    }

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        try {
            $backupService->uploadBackup($this->backupRecord, $this->localFilePath);
            Log::info("Asynchronous upload completed for Backup ID: {$this->backupRecord->id}");
        } catch (\Exception $e) {
            Log::error("Asynchronous upload failed for Backup ID: {$this->backupRecord->id}. Error: " . $e->getMessage());
            // No need to throw if BackupService already handled status update, 
            // unless we want Laravel to retry. Given large files, retry might be tricky.
            throw $e;
        }
    }
}

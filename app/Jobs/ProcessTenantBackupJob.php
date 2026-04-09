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

class ProcessTenantBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $backupRecord;

    /**
     * Create a new job instance.
     */
    public function __construct(TenantBackup $backupRecord)
    {
        $this->backupRecord = $backupRecord;
    }

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        try {
            if ($this->backupRecord->type === 'global') {
                // Using a private/protected helper call via a public wrapper or reflection
                // but since it's in the same namespace context or Service, let's just
                // call a public method. I'll make generateGlobalBackup public in BackupService.
                $backupService->generateGlobalBackup($this->backupRecord);
            } else {
                $backupService->generateBackup($this->backupRecord);
            }
            Log::info("Backup generation completed for Record ID: {$this->backupRecord->id} (Type: {$this->backupRecord->type})");
        } catch (\Exception $e) {
            Log::error("Backup generation failed for Record ID: {$this->backupRecord->id}. Error: ".$e->getMessage());
            throw $e;
        }
    }
}

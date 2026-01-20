<?php

namespace App\Jobs;

use App\Models\Team;
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

    protected $team;

    /**
     * Create a new job instance.
     */
    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        try {
            $backupService->backupTenant($this->team);
            Log::info("Background backup completed for Team ID: {$this->team->id}");
        } catch (\Exception $e) {
            Log::error("Background backup failed for Team ID: {$this->team->id}. Error: " . $e->getMessage());
            throw $e;
        }
    }
}

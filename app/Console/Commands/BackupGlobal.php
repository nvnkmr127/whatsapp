<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;

class BackupGlobal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:global';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a global system-wide backup';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService)
    {
        $this->info("Starting global system backup...");

        try {
            $backupName = $backupService->backupGlobal();
            $this->info("Successfully created global backup: {$backupName}");
        } catch (\Exception $e) {
            $this->error("Global backup failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

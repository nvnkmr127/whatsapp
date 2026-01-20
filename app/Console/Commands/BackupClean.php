<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\PruneOldBackupsJob;

class BackupClean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove backups older than 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Queuing backup cleanup job (7-day retention policy)...");

        PruneOldBackupsJob::dispatch();

        $this->info("Cleanup job has been dispatched.");

        return 0;
    }
}

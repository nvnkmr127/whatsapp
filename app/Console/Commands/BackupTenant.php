<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Jobs\ProcessTenantBackupJob;

class BackupTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:tenant {id : The ID of the team/tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a backup for a specific tenant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        $team = Team::find($id);

        if (!$team) {
            $this->error("Team with ID {$id} not found.");
            return 1;
        }

        $this->info("Queuing backup for Team: {$team->name} (ID: {$team->id})...");

        ProcessTenantBackupJob::dispatch($team);

        $this->info("Backup job has been dispatched to the queue.");

        return 0;
    }
}

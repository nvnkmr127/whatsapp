<?php

namespace App\Console\Commands;

use App\Jobs\CreateHealthSnapshotJob;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateHealthScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:calculate-health-scores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue WhatsApp health snapshot jobs for all connected teams';

    /**
     * Execute the console command.
     *
     * Dispatches one job per team rather than snapshotting inline: each snapshot
     * makes several Meta API calls, so a sequential loop meant one slow team
     * delayed everyone behind it and one failure was lost with no retry.
     */
    public function handle(): int
    {
        $queued = 0;

        Team::whereNotNull('whatsapp_access_token')
            ->chunkById(100, function ($teams) use (&$queued): void {
                foreach ($teams as $team) {
                    // Stagger dispatch so we trickle into Meta instead of firing
                    // every team at once and tripping app-level rate limits.
                    // ponytail: flat 2s stagger capped at 10 min; if team count
                    // outgrows that, spread proportionally over the run interval.
                    CreateHealthSnapshotJob::dispatch($team)
                        ->delay(now()->addSeconds(min($queued * 2, 600)));

                    $queued++;
                }
            });

        $this->info("Queued {$queued} health snapshot job(s).");
        Log::info('Health snapshot run dispatched', ['teams' => $queued]);

        return self::SUCCESS;
    }
}

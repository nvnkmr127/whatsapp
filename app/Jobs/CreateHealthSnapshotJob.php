<?php

namespace App\Jobs;

use App\Models\Team;
use App\Services\WhatsAppHealthMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * One health snapshot for one team.
 *
 * Previously this ran inline inside whatsapp:calculate-health-scores, so a
 * single hanging Meta call stalled every team queued behind it and any failure
 * left a permanent gap in that team's trend with no retry.
 */
class CreateHealthSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /** checkHealth() makes several Meta calls; cap it so one team can't hold a worker. */
    public int $timeout = 90;

    public array $backoff = [30];

    public function __construct(public Team $team) {}

    public function handle(WhatsAppHealthMonitor $monitor): void
    {
        $monitor->createSnapshot($this->team);
    }

    public function failed(\Throwable $e): void
    {
        // Surfaces in failed_jobs as well, but log with team context so a team
        // that has been failing for days is greppable.
        Log::error('Health snapshot failed', [
            'team_id' => $this->team->id,
            'error' => $e->getMessage(),
        ]);
    }
}

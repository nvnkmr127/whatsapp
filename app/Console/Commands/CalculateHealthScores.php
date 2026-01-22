<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\WhatsAppHealthMonitor;
use Illuminate\Console\Command;

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
    protected $description = 'Calculate and store WhatsApp health scores for all teams';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppHealthMonitor $monitor)
    {
        $this->info('Calculating WhatsApp health scores...');

        $teamsProcessed = 0;
        $healthyCount = 0;
        $warningCount = 0;
        $criticalCount = 0;

        Team::whereNotNull('whatsapp_access_token')
            ->chunk(100, function ($teams) use ($monitor, &$teamsProcessed, &$healthyCount, &$warningCount, &$criticalCount) {
                foreach ($teams as $team) {
                    try {
                        // Create health snapshot
                        $snapshot = $monitor->createSnapshot($team);

                        // Count by status
                        match ($snapshot->health_status) {
                            'healthy' => $healthyCount++,
                            'warning' => $warningCount++,
                            'critical' => $criticalCount++,
                        };

                        $teamsProcessed++;

                    } catch (\Exception $e) {
                        $this->error("Failed to calculate health for team {$team->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("\nHealth Score Summary:");
        $this->table(
            ['Status', 'Count'],
            [
                ['🟢 Healthy', $healthyCount],
                ['🟡 Warning', $warningCount],
                ['🔴 Critical', $criticalCount],
                ['Total', $teamsProcessed],
            ]
        );

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\WhatsAppSetupState;
use App\Models\Team;
use App\Services\WhatsAppSetupStateMachine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DetectStuckSetups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:detect-stuck-setups {--fix : Automatically fix stuck setups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and optionally fix WhatsApp setups stuck in non-terminal states';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppSetupStateMachine $stateMachine)
    {
        $this->info('Checking for stuck WhatsApp setups...');

        $stuckCount = 0;
        $fixedCount = 0;

        Team::where('whatsapp_setup_in_progress', true)
            ->whereNotNull('whatsapp_setup_started_at')
            ->chunk(100, function ($teams) use ($stateMachine, &$stuckCount, &$fixedCount) {
                foreach ($teams as $team) {
                    if ($stateMachine->isStuck($team)) {
                        $stuckCount++;
                        $this->handleStuckSetup($team, $stateMachine, $fixedCount);
                    }
                }
            });

        $this->info("Found {$stuckCount} stuck setups.");

        if ($this->option('fix')) {
            $this->info("Fixed {$fixedCount} stuck setups.");
        }

        return Command::SUCCESS;
    }

    /**
     * Handle a stuck setup
     */
    private function handleStuckSetup(Team $team, WhatsAppSetupStateMachine $stateMachine, int &$fixedCount): void
    {
        $currentState = $stateMachine->getCurrentState($team);
        $elapsed = $team->whatsapp_setup_started_at->diffInMinutes(now());

        $this->warn("Team {$team->id}: Stuck in {$currentState->value} for {$elapsed} minutes");

        Log::warning("Stuck WhatsApp setup detected", [
            'team_id' => $team->id,
            'state' => $currentState->value,
            'elapsed_minutes' => $elapsed,
            'started_at' => $team->whatsapp_setup_started_at,
        ]);

        if ($this->option('fix')) {
            try {
                // Rollback to NOT_CONFIGURED
                $stateMachine->rollback($team);

                $this->info("  → Rolled back to NOT_CONFIGURED");
                $fixedCount++;

                // TODO: Notify team owner about stuck setup

            } catch (\Exception $e) {
                $this->error("  → Failed to fix: {$e->getMessage()}");
                Log::error("Failed to fix stuck setup", [
                    'team_id' => $team->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

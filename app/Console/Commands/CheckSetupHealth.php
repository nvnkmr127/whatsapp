<?php

namespace App\Console\Commands;

use App\Enums\WhatsAppSetupState;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSetupHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:check-setup-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check health of WhatsApp setups and alert on issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking WhatsApp setup health...');

        $issues = [
            'degraded' => 0,
            'suspended' => 0,
            'failed_retries' => 0,
            'token_expiring' => 0,
        ];

        // Check for degraded setups
        $degradedTeams = Team::where('whatsapp_setup_state', WhatsAppSetupState::DEGRADED->value)->get();
        $issues['degraded'] = $degradedTeams->count();

        foreach ($degradedTeams as $team) {
            $duration = $team->whatsapp_setup_completed_at?->diffInHours(now()) ?? 0;

            if ($duration > 24) {
                $this->warn("Team {$team->id}: In DEGRADED state for {$duration} hours");

                Log::warning("Setup degraded for extended period", [
                    'team_id' => $team->id,
                    'duration_hours' => $duration,
                ]);
            }
        }

        // Check for suspended setups
        $suspendedTeams = Team::where('whatsapp_setup_state', WhatsAppSetupState::SUSPENDED->value)->get();
        $issues['suspended'] = $suspendedTeams->count();

        foreach ($suspendedTeams as $team) {
            $this->error("Team {$team->id}: SUSPENDED");

            Log::critical("Setup suspended", [
                'team_id' => $team->id,
                'quality_rating' => $team->wm_quality_rating,
            ]);
        }

        // Check for failed retries
        $failedRetries = Team::where('whatsapp_setup_retry_count', '>=', 3)
            ->where('whatsapp_setup_in_progress', true)
            ->get();
        $issues['failed_retries'] = $failedRetries->count();

        foreach ($failedRetries as $team) {
            $this->warn("Team {$team->id}: {$team->whatsapp_setup_retry_count} failed retries");

            Log::warning("Multiple setup retry failures", [
                'team_id' => $team->id,
                'retry_count' => $team->whatsapp_setup_retry_count,
                'state' => $team->whatsapp_setup_state,
            ]);
        }

        // Check for expiring tokens in active setups
        $expiringTokens = Team::where('whatsapp_setup_state', WhatsAppSetupState::ACTIVE->value)
            ->whereNotNull('whatsapp_token_expires_at')
            ->where('whatsapp_token_expires_at', '<', now()->addDays(7))
            ->get();
        $issues['token_expiring'] = $expiringTokens->count();

        foreach ($expiringTokens as $team) {
            $daysRemaining = $team->whatsapp_token_expires_at->diffInDays(now());
            $this->warn("Team {$team->id}: Token expires in {$daysRemaining} days");
        }

        // Summary
        $this->info("\nHealth Check Summary:");
        $this->table(
            ['Issue', 'Count'],
            [
                ['Degraded', $issues['degraded']],
                ['Suspended', $issues['suspended']],
                ['Failed Retries (3+)', $issues['failed_retries']],
                ['Tokens Expiring (<7 days)', $issues['token_expiring']],
            ]
        );

        $totalIssues = array_sum($issues);

        if ($totalIssues > 0) {
            $this->warn("\nTotal issues found: {$totalIssues}");
            return Command::FAILURE;
        }

        $this->info("\n✓ All setups healthy");
        return Command::SUCCESS;
    }
}

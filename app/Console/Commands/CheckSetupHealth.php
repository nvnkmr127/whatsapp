<?php

namespace App\Console\Commands;

use App\Enums\IntegrationState;
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

        // Degraded setups
        $degradedTeams = Team::whereIn('whatsapp_setup_state', [
            IntegrationState::DEGRADED->value,
            IntegrationState::READY_WARNING->value,
        ])->get();

        foreach ($degradedTeams as $team) {
            $duration = $team->whatsapp_setup_completed_at?->diffInHours(now()) ?? 0;

            if ($duration > 24) {
                $this->warn("Team {$team->id}: degraded for {$duration} hours");

                Log::warning('Setup degraded for extended period', [
                    'team_id' => $team->id,
                    'duration_hours' => $duration,
                ]);
            }
        }

        // Suspended / restricted setups
        $suspendedTeams = Team::whereIn('whatsapp_setup_state', [
            IntegrationState::SUSPENDED->value,
            IntegrationState::RESTRICTED->value,
        ])->get();

        foreach ($suspendedTeams as $team) {
            $this->error("Team {$team->id}: {$team->whatsapp_setup_state->value}");

            Log::critical('Setup suspended', [
                'team_id' => $team->id,
                'quality_rating' => $team->whatsapp_quality_rating,
            ]);
        }

        // Expiring tokens in active setups
        $expiringTokens = Team::whereIn('whatsapp_setup_state', [
            IntegrationState::READY->value,
            IntegrationState::READY_WARNING->value,
        ])
            ->whereNotNull('whatsapp_token_expires_at')
            ->where('whatsapp_token_expires_at', '<', now()->addDays(7))
            ->get();

        foreach ($expiringTokens as $team) {
            $daysRemaining = $team->whatsapp_token_expires_at->diffInDays(now());
            $this->warn("Team {$team->id}: Token expires in {$daysRemaining} days");
        }

        $issues = [
            'Degraded' => $degradedTeams->count(),
            'Suspended/Restricted' => $suspendedTeams->count(),
            'Tokens Expiring (<7 days)' => $expiringTokens->count(),
        ];

        $this->info("\nHealth Check Summary:");
        $this->table(['Issue', 'Count'], collect($issues)->map(fn ($c, $k) => [$k, $c])->values()->all());

        if (array_sum($issues) > 0) {
            $this->warn("\nTotal issues found: ".array_sum($issues));

            return Command::FAILURE;
        }

        $this->info("\n✓ All setups healthy");

        return Command::SUCCESS;
    }
}

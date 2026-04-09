<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\TeamTransaction;
use App\Services\BillingService;
use Illuminate\Console\Command;

class BackfillOfferCredit extends Command
{
    protected $signature = 'billing:backfill-offer-credit
                            {team_id? : Specific team ID to repair}
                            {--dry-run : Report missing credits without applying them}';

    protected $description = 'Backfill missing launch-offer wallet credit for teams that already claimed the offer';

    public function handle(BillingService $billing): int
    {
        $teamId = $this->argument('team_id');
        $dryRun = (bool) $this->option('dry-run');
        $credit = (float) get_setting('offer_initial_credit', 5.00);

        if ($credit <= 0) {
            $this->warn('offer_initial_credit is 0. Nothing to backfill.');

            return self::SUCCESS;
        }

        $teams = Team::query()
            ->when($teamId, fn ($query) => $query->where('id', $teamId))
            ->whereNotNull('offer_claimed_at')
            ->get();

        if ($teams->isEmpty()) {
            $this->warn($teamId ? "No claimed-offer team found for ID {$teamId}." : 'No claimed-offer teams found.');

            return self::SUCCESS;
        }

        $repaired = 0;
        $skipped = 0;

        foreach ($teams as $team) {
            $hasWelcomeCredit = TeamTransaction::query()
                ->where('team_id', $team->id)
                ->where('description', 'like', 'Welcome Gift (Launch Offer%')
                ->exists();

            if ($hasWelcomeCredit) {
                $skipped++;
                $this->line("Skipping team {$team->id}: welcome credit transaction already exists.");

                continue;
            }

            if ($dryRun) {
                $this->warn("Would backfill {$credit} to team {$team->id} ({$team->name}).");
                $repaired++;

                continue;
            }

            $billing->deposit($team, $credit, 'Welcome Gift (Launch Offer Backfill)');
            $repaired++;
            $this->info("Backfilled {$credit} to team {$team->id} ({$team->name}).");
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['backfilled_or_reported', $repaired],
            ['skipped_existing_credit', $skipped],
        ]);

        return self::SUCCESS;
    }
}

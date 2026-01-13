<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Services\BillingService;

class BillingSeeder extends Seeder
{
    public function run()
    {
        $team = Team::first();
        if (!$team) {
            $this->command->info('No team found.');
            return;
        }

        $this->command->info("Seeding wallet for team: {$team->name}");

        $billing = new BillingService();
        $billing->deposit($team, 100, 'Test Credit Seed'); // Add $100

        $this->command->info('Deposited $100 credits.');
    }
}

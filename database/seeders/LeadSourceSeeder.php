<?php

namespace Database\Seeders;

use App\Models\LeadSource;
use App\Models\Team;
use Illuminate\Database\Seeder;

class LeadSourceSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::all();

        foreach ($teams as $team) {
            $sources = [
                ['name' => 'Organic Search', 'type' => 'organic'],
                ['name' => 'Paid Search', 'type' => 'paid'],
                ['name' => 'Referral', 'type' => 'referral'],
                ['name' => 'Social Media', 'type' => 'social'],
                ['name' => 'Email Marketing', 'type' => 'email'],
                ['name' => 'Direct Traffic', 'type' => 'direct'],
                ['name' => 'Offline Event', 'type' => 'offline'],
            ];

            foreach ($sources as $source) {
                LeadSource::firstOrCreate(
                    ['team_id' => $team->id, 'name' => $source['name']],
                    ['type' => $source['type']]
                );
            }
        }
    }
}

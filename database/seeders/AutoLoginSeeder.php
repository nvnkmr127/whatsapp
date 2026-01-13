<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Jetstream;

class AutoLoginSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
            ]
        );

        if (!$superAdmin->ownedTeams()->where('personal_team', true)->exists()) {
            $superAdmin->ownedTeams()->save(Team::forceCreate([
                'user_id' => $superAdmin->id,
                'name' => "Admin's Team",
                'personal_team' => true,
            ]));
        }

        $adminTeam = $superAdmin->ownedTeams()->where('personal_team', true)->first();
        $superAdmin->current_team_id = $adminTeam->id;
        $superAdmin->save();

        // 2. Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Team Manager',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
            ]
        );

        if (!$manager->ownedTeams()->where('personal_team', true)->exists()) {
            $manager->ownedTeams()->save(Team::forceCreate([
                'user_id' => $manager->id,
                'name' => "Marketing Team",
                'personal_team' => true,
            ]));
        }

        $managerTeam = $manager->ownedTeams()->where('personal_team', true)->first();
        $manager->current_team_id = $managerTeam->id;
        $manager->save();

        // 3. Agent
        $agent = User::updateOrCreate(
            ['email' => 'agent@example.com'],
            [
                'name' => 'Support Agent',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
            ]
        );

        // Add Agent to Manager's Team
        if (!$managerTeam->users->contains($agent->id)) {
            $managerTeam->users()->attach($agent, ['role' => 'agent']);
        }

        $agent->current_team_id = $managerTeam->id;
        $agent->save();
    }
}

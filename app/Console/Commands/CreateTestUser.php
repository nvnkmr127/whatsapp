<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    protected $signature = 'user:test';
    protected $description = 'Create a test user for smoke testing';

    public function handle()
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        if (!$user->currentTeam) {
            $team = \App\Models\Team::where('user_id', $user->id)->first();

            if (!$team) {
                $team = new \App\Models\Team();
                $team->user_id = $user->id;
                $team->name = 'Test Team';
                $team->personal_team = true;
                $team->save();
            }

            $user->current_team_id = $team->id;
            $user->save();
        }

        $this->info('User created: admin@example.com / password');
    }
}

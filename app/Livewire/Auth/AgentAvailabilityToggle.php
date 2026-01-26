<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgentAvailabilityToggle extends Component
{
    public $isAvailable;

    public function mount()
    {
        $user = Auth::user();
        if (!$user || !$user->currentTeam) {
            $this->isAvailable = false;
            return;
        }

        $row = \Illuminate\Support\Facades\DB::table('team_user')
            ->where('team_id', $user->currentTeam->id)
            ->where('user_id', $user->id)
            ->first();

        $this->isAvailable = $row ? (bool) $row->is_call_enabled : false;

        Log::info("AgentAvailabilityToggle mount: User {$user->id}, Team {$user->currentTeam->id}, isAvailable: " . ($this->isAvailable ? 'true' : 'false'));
    }

    public function toggleAvailability()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team)
            return;

        // Toggle state
        $this->isAvailable = !$this->isAvailable;

        try {
            $affected = \Illuminate\Support\Facades\DB::table('team_user')
                ->where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->update([
                    'is_call_enabled' => $this->isAvailable,
                    'call_status' => $this->isAvailable ? 'available' : 'unavailable',
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                // If no rows updated, check if row exists.
                // If it exists, values were same (no-op). If not, we need to insert (Owner case).
                $exists = \Illuminate\Support\Facades\DB::table('team_user')
                    ->where('team_id', $team->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$exists) {
                    \Illuminate\Support\Facades\DB::table('team_user')->insert([
                        'team_id' => $team->id,
                        'user_id' => $user->id,
                        'role' => 'admin', // Default to admin for owner
                        'is_call_enabled' => $this->isAvailable,
                        'call_status' => $this->isAvailable ? 'available' : 'unavailable',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::info("AgentAvailabilityToggle: Created missing team_user pivot row for User {$user->id} (Team {$team->id})");
                }
            }

            Log::info("AgentAvailabilityToggle: User {$user->id}, Team {$team->id} status synced. New State: " . ($this->isAvailable ? 'true' : 'false'));

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Status updated: ' . ($this->isAvailable ? 'Available for calls' : 'Calls disabled')
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update agent availability: " . $e->getMessage());

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to sync status. Please check your connection.'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.auth.agent-availability-toggle');
    }
}

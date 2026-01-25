<?php

namespace App\Jobs;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckTrialExpiry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $daysToCheck = [7, 3, 1];

        foreach ($daysToCheck as $days) {
            $date = now()->addDays($days)->format('Y-m-d');

            $teams = Team::where('subscription_status', 'trial')
                ->whereDate('trial_ends_at', $date)
                ->get();

            foreach ($teams as $team) {
                $this->notifyTeam($team, $days);
            }
        }

        // Handle Expired Trials (Expired yesterday)
        $expiredDate = now()->subDay()->format('Y-m-d');
        $expiredTeams = Team::where('subscription_status', 'trial')
            ->whereDate('trial_ends_at', $expiredDate)
            ->get();

        foreach ($expiredTeams as $team) {
            $team->update(['subscription_status' => 'expired']);
            Log::info("Team {$team->id} trial expired.");
            // Notify Expired
        }
    }

    protected function notifyTeam(Team $team, $daysRemaining)
    {
        // For MVP, just log. In real imp, use Mail::to($team->owner)->send(new TrialExpiring($daysRemaining));
        Log::info("Trial expiring for Team {$team->id} in {$daysRemaining} days. Email sent to owner.");
    }
}

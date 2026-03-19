<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Models\Message;
use App\Models\TenantBackup;
use Illuminate\Support\Facades\Cache;

class AdminAnalyticsService
{
    /**
     * Get global dashboard statistics.
     */
    public function getGlobalStats(): array
    {
        // Cache these for 5 minutes since they hit large tables
        return Cache::remember('super_admin_stats', 300, function () {
            return [
                'total_teams' => Team::count(),
                'active_subs' => Team::where('subscription_status', 'active')->count(),
                'total_users' => User::count(),
                'total_messages' => Message::count(),
                'total_backups' => TenantBackup::count(),
                'global_backups' => TenantBackup::whereNull('team_id')->count(),
            ];
        });
    }

    /**
     * Search for teams and users.
     */
    public function search(string $search, ?string $status = null)
    {
        $query = Team::with('owner')->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('subscription_status', $status);
        }

        $matchingUsers = collect();
        if ($search) {
            $matchingUsers = User::where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->with('ownedTeams')
                ->limit(10)
                ->get();
        }

        return [
            'teams' => $query->paginate(20, ['*'], 'teams')->withQueryString(),
            'matchingUsers' => $matchingUsers,
        ];
    }
}

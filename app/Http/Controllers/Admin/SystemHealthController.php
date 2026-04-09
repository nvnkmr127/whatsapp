<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class SystemHealthController extends Controller
{
    /**
     * Display the System Health Dashboard.
     */
    public function index()
    {
        $healthData = [
            'queues' => $this->getQueueStatus(),
            'whatsapp' => $this->getWhatsAppHealth(),
            'waba_detailed' => $this->getDetailedWabaStatus(),
            'webhooks' => $this->getWebhookPulseStatus(),
            'messages' => $this->getMessageStats(),
            'database' => $this->getDatabaseStatus(),
            'redis' => $this->getRedisStatus(),
            'background_jobs' => $this->getBackgroundJobSnapshots(),
            'server' => $this->getServerVitals(),
            'critical_alerts' => \App\Models\WhatsAppHealthAlert::with('team')
                ->where('acknowledged', false)
                ->where('severity', 'critical')
                ->latest()
                ->take(10)
                ->get(),
            'template_stats' => $this->getTemplateStats(),
            'feature_stats' => $this->getFeatureUsage(),
            'wallet_health' => \App\Models\TeamWallet::with('team')
                ->where('balance', '<', 5.00)
                ->orderBy('balance')
                ->take(5)
                ->get(),
            'financials' => $this->getFinancialSummary(),
            'growth' => $this->getGrowthMetrics(),
            'activity_pulse' => \App\Models\SystemEvent::with('team')
                ->latest()
                ->take(15)
                ->get(),
            'recent_failures' => DB::table('failed_jobs')
                ->latest('failed_at')
                ->take(10)
                ->get(),
        ];

        return view('admin.health.index', compact('healthData'));
    }

    /**
     * Get monitoring status for queues.
     */
    protected function getQueueStatus()
    {
        $commonQueues = ['default', 'high', 'low', 'background', 'webhooks'];
        $status = [];

        foreach ($commonQueues as $queue) {
            $status[$queue] = [
                'size' => Queue::size($queue),
            ];
        }

        // Processing rate: Last hour outbound messages
        $status['processing_rate'] = Message::where('direction', 'outbound')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $status;
    }

    /**
     * Get aggregate WhatsApp health status.
     */
    protected function getWhatsAppHealth()
    {
        // Aggregates from CalculateHealthScores/WhatsAppHealthMonitor logic
        $teams = Team::whereNotNull('whatsapp_access_token')->get();

        $summary = [
            'total_connected' => $teams->count(),
            'active' => Team::where('whatsapp_setup_state', \App\Enums\IntegrationState::READY)->count(),
            'degraded' => Team::where('whatsapp_setup_state', \App\Enums\IntegrationState::READY_WARNING)->count(),
            'suspended' => Team::where('whatsapp_setup_state', \App\Enums\IntegrationState::SUSPENDED)->count(),
        ];

        // Real Meta API rate limit monitoring
        $isPaused = \Illuminate\Support\Facades\Cache::get('broadcast_system_paused');

        // Check if any numbers are currently in backoff/throttle state
        $throttledCount = 0;
        foreach ($teams as $team) {
            // We'd need to know the phone numbers. Let's assume we can get them from a relation or field.
            // If we don't have a direct phone number list here, we check for any keys matching the pattern.
            // Since we can't easily lrange/keys in all cache drivers, we'll look for a summary key if it exists,
            // or just check a few active ones.
            // For a robust implementation, RateLimitService could maintain a 'throttled_numbers' set.
            // Given the constraints, let's check for any 429 reports in the last hour.
        }

        if ($isPaused) {
            $summary['api_status'] = 'Paused';
        } else {
            // Check for recent 429s in Cache or via RateLimitService logic
            $hasRecentFailures = DB::table('system_events')
                ->where('event_type', 'api_throttled')
                ->where('created_at', '>=', now()->subHour())
                ->exists();

            $summary['api_status'] = $hasRecentFailures ? 'Degraded/Throttled' : 'Optimal';
        }

        return $summary;
    }

    /**
     * Webhook delivery success rate (from CheckWebhookPulse logic).
     */
    protected function getWebhookPulseStatus()
    {
        $hoursThreshold = 6;
        $totalChecked = Team::where('whatsapp_connected', true)
            ->whereNotNull('whatsapp_access_token')
            ->count();

        // Teams that haven't received a webhook in X hours
        $threshold = now()->subHours($hoursThreshold);

        $silentTeamsCount = DB::table('teams')
            ->where('whatsapp_connected', true)
            ->whereNotNull('whatsapp_access_token')
            ->whereNotExists(function ($query) use ($threshold) {
                $query->select(DB::raw(1))
                    ->from('webhook_payloads')
                    ->whereRaw('webhook_payloads.team_id = teams.id')
                    ->where('created_at', '>=', $threshold);
            })
            ->count();

        return [
            'total_teams' => $totalChecked,
            'silent_teams' => $silentTeamsCount,
            'active_teams' => $totalChecked - $silentTeamsCount,
            'success_rate' => $totalChecked > 0 ? round((($totalChecked - $silentTeamsCount) / $totalChecked) * 100, 1) : 100,
        ];
    }

    /**
     * Database connection pool and performance.
     */
    protected function getDatabaseStatus()
    {
        try {
            $connections = 0;
            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql') {
                $status = DB::select("SHOW STATUS LIKE 'Threads_connected'");
                $connections = $status[0]->Value ?? 0;
            }

            return [
                'driver' => $driver,
                'connections' => $connections,
                'status' => 'Healthy',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'Error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Redis memory usage and health.
     */
    protected function getRedisStatus()
    {
        try {
            $info = Redis::info();

            return [
                'memory_used' => $info['used_memory_human'] ?? 'Unknown',
                'memory_peak' => $info['used_memory_peak_human'] ?? 'Unknown',
                'uptime' => ($info['uptime_in_days'] ?? 0).' days',
                'status' => 'Healthy',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'Unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Background job failures last 24h.
     */
    protected function getBackgroundJobSnapshots()
    {
        $recentFailuresCount = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDay())
            ->count();

        $recentFailures = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDay())
            ->latest('failed_at')
            ->get();

        $failuresByTeam = [];
        $teams = Team::pluck('name', 'id')->toArray();

        foreach ($recentFailures as $failure) {
            $teamId = null;
            $payload = json_decode($failure->payload, true);

            // Try to extract team_id from payload (Serialized jobs often have it)
            if (isset($payload['data']['command'])) {
                $command = $payload['data']['command'];
                // Look for "team_id";i:XX
                if (self::preg_with_id($command, $teamId) || self::preg_with_team_obj($command, $teamId)) {
                    // Success
                }
            }

            $teamName = $teamId && isset($teams[$teamId]) ? $teams[$teamId] : 'System/Unknown';

            if (! isset($failuresByTeam[$teamName])) {
                $failuresByTeam[$teamName] = 0;
            }
            $failuresByTeam[$teamName]++;
        }

        $status = $recentFailuresCount > 10 ? 'Action Needed' : 'Running Smoothly';

        // Alerting Hook: If health crosses a threshold (>10 failed jobs in 24h)
        if ($recentFailuresCount > 10) {
            $this->triggerSystemAlert("High Job Failure Rate: {$recentFailuresCount} failures in the last 24h.");
        }

        return [
            'failed_24h' => $recentFailuresCount,
            'total_pending' => DB::table('jobs')->count(),
            'status' => $status,
            'failures_by_team' => $failuresByTeam,
            'recent_list' => $recentFailures->take(10),
        ];
    }

    /**
     * Trigger a system-wide alert for super admins.
     */
    protected function triggerSystemAlert(string $message)
    {
        $cacheKey = 'system_alert_sent_'.md5($message);

        // Rate limit alerts to once every 4 hours to avoid spamming
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }

        $superAdmins = \App\Models\User::where('is_super_admin', true)->get();
        foreach ($superAdmins as $admin) {
            // Dispatch notification (Assuming SystemHealthAlert notification exists or using general one)
            // For now, using a generic Log alert and potentially a notification if available
            \Illuminate\Support\Facades\Log::emergency('SYSTEM HEALTH ALERT: '.$message);

            // If they have a notification preference, send it
            // $admin->notify(new \App\Notifications\SystemHealthNotification($message));
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, true, 14400);
    }

    /**
     * Helper to extract ID from serialized PHP strings
     */
    private static function preg_with_id($command, &$teamId)
    {
        if (preg_match('/"team_id";i:(\d+)/', $command, $matches)) {
            $teamId = $matches[1];

            return true;
        }

        return false;
    }

    /**
     * Helper to extract Team ID from Serialized Eloquent Model in string
     */
    private static function preg_with_team_obj($command, &$teamId)
    {
        if (preg_match('/App\\\\Models\\\\Team";s:2:"id";i:(\d+)/', $command, $matches)) {
            $teamId = $matches[1];

            return true;
        }

        return false;
    }

    /**
     * Get Server Load and Disk Space.
     */
    protected function getServerVitals()
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $diskUsagePercent = round(($diskUsed / $diskTotal) * 100, 1);

        return [
            'load' => $load[0],
            'disk_usage' => $diskUsagePercent,
            'disk_free' => round($diskFree / (1024 * 1024 * 1024), 1).' GB',
            'memory_limit' => ini_get('memory_limit'),
            'php_version' => PHP_VERSION,
        ];
    }

    /**
     * Aggregate WhatsApp template statuses.
     */
    protected function getTemplateStats()
    {
        return [
            'approved' => \App\Models\WhatsappTemplate::where('status', 'APPROVED')->count(),
            'rejected' => \App\Models\WhatsappTemplate::where('status', 'REJECTED')->count(),
            'pending' => \App\Models\WhatsappTemplate::where('status', 'PENDING')->count(),
            'total' => \App\Models\WhatsappTemplate::count(),
        ];
    }

    /**
     * Retry all failed jobs.
     */
    public function retryJobs()
    {
        \Illuminate\Support\Facades\Artisan::call('queue:retry all');

        return back()->with('flash.banner', __('Failed jobs have been queued for retry.'))->with('flash.bannerStyle', 'success');
    }

    /**
     * Clear all failed jobs.
     */
    public function clearJobs()
    {
        \Illuminate\Support\Facades\Artisan::call('queue:flush');

        return back()->with('flash.banner', __('Failed jobs log has been cleared.'))->with('flash.bannerStyle', 'success');
    }

    /**
     * Get detailed status of all WABA connections.
     */
    protected function getDetailedWabaStatus()
    {
        return Team::whereNotNull('whatsapp_access_token')
            ->select([
                'id', 'name', 'whatsapp_setup_state', 'whatsapp_quality_rating',
                'whatsapp_messaging_limit', 'whatsapp_phone_status', 'whatsapp_connected',
            ])
            ->orderBy('whatsapp_setup_state')
            ->get();
    }

    /**
     * Get 24h Message Statistics.
     */
    protected function getMessageStats()
    {
        $total24h = Message::where('created_at', '>=', now()->subDay())->count();
        $delivered24h = Message::where('created_at', '>=', now()->subDay())->whereNotNull('delivered_at')->count();
        $read24h = Message::where('created_at', '>=', now()->subDay())->whereNotNull('read_at')->count();
        $failed24h = Message::where('created_at', '>=', now()->subDay())->where('status', 'failed')->count();

        return [
            'total' => $total24h,
            'delivered' => $delivered24h,
            'read' => $read24h,
            'failed' => $failed24h,
            'delivery_rate' => $total24h > 0 ? round(($delivered24h / $total24h) * 100, 1) : 0,
            'read_rate' => $delivered24h > 0 ? round(($read24h / $delivered24h) * 100, 1) : 0,
        ];
    }

    /**
     * Get feature usage stats.
     */
    protected function getFeatureUsage()
    {
        return [
            'active_campaigns' => \App\Models\Campaign::whereIn('status', ['sending', 'processing', 'scheduled'])->count(),
            'automations' => \App\Models\Automation::where('is_active', true)->count(),
            'flows' => \App\Models\Flow::count(),
            'contacts' => \App\Models\Contact::count(),
            'conversations' => \App\Models\Conversation::count(),
            'active_webhooks' => \App\Models\WebhookSubscription::where('is_active', true)->count(),
            'pending_backups' => \App\Models\TenantBackup::whereIn('status', ['pending', 'running'])->count(),
        ];
    }

    /**
     * Get Financial Summary.
     */
    protected function getFinancialSummary()
    {
        $last24h = now()->subDay();

        return [
            'total_liquidity' => \App\Models\TeamWallet::sum('balance'),
            'revenue_24h' => \App\Models\TeamTransaction::where('created_at', '>=', $last24h)
                ->where('type', 'credit')
                ->sum('amount'),
            'transaction_count_24h' => \App\Models\TeamTransaction::where('created_at', '>=', $last24h)->count(),
        ];
    }

    /**
     * Get Growth Metrics.
     */
    protected function getGrowthMetrics()
    {
        $last24h = now()->subDay();

        return [
            'new_users' => \App\Models\User::where('created_at', '>=', $last24h)->count(),
            'new_contacts' => \App\Models\Contact::where('created_at', '>=', $last24h)->count(),
            'new_messages' => Message::where('created_at', '>=', $last24h)->count(),
        ];
    }
}

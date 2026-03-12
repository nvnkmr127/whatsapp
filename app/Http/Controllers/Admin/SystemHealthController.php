<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Team;
use App\Models\WebhookPayload;
use Illuminate\Http\Request;
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

        // Rate limit status (Meta)
        // Note: For actual rate limit status, we'd need to scrape Meta's headers 
        // during regular API calls and store them in a cache/table.
        // For now, we'll show a placeholder or "Normal" if no 429s in logs recently.
        $summary['api_status'] = 'Optimal'; // Placeholder

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
                'uptime' => ($info['uptime_in_days'] ?? 0) . ' days',
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
                if (preg_with_id($command, $teamId) || preg_with_team_obj($command, $teamId)) {
                    // Success
                }
            }

            $teamName = $teamId && isset($teams[$teamId]) ? $teams[$teamId] : 'System/Unknown';
            
            if (!isset($failuresByTeam[$teamName])) {
                $failuresByTeam[$teamName] = 0;
            }
            $failuresByTeam[$teamName]++;
        }

        return [
            'failed_24h' => $recentFailures->count(),
            'total_pending' => DB::table('jobs')->count(),
            'status' => $recentFailures->count() > 10 ? 'Action Needed' : 'Running Smoothly',
            'failures_by_team' => $failuresByTeam,
            'recent_list' => $recentFailures->take(10),
        ];
    }

    /**
     * Get Server Load and Disk Space.
     */
    protected function getServerVitals()
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $diskFree = disk_free_space("/");
        $diskTotal = disk_total_space("/");
        $diskUsed = $diskTotal - $diskFree;
        $diskUsagePercent = round(($diskUsed / $diskTotal) * 100, 1);

        return [
            'load' => $load[0],
            'disk_usage' => $diskUsagePercent,
            'disk_free' => round($diskFree / (1024 * 1024 * 1024), 1) . ' GB',
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
        return back()->with('flash.banner', 'Failed jobs have been queued for retry.')->with('flash.bannerStyle', 'success');
    }

    /**
     * Clear all failed jobs.
     */
    public function clearJobs()
    {
        \Illuminate\Support\Facades\Artisan::call('queue:flush');
        return back()->with('flash.banner', 'Failed jobs log has been cleared.')->with('flash.bannerStyle', 'success');
    }

    /**
     * Get detailed status of all WABA connections.
     */
    protected function getDetailedWabaStatus()
    {
        return Team::whereNotNull('whatsapp_access_token')
            ->select([
                'id', 'name', 'whatsapp_setup_state', 'whatsapp_quality_rating', 
                'whatsapp_messaging_limit', 'whatsapp_phone_status', 'whatsapp_connected'
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

/**
 * Helper to extract ID from serialized PHP strings
 */
function preg_with_id($command, &$teamId) {
    if (preg_match('/"team_id";i:(\d+)/', $command, $matches)) {
        $teamId = $matches[1];
        return true;
    }
    return false;
}

/**
 * Helper to extract Team ID from Serialized Eloquent Model in string
 */
function preg_with_team_obj($command, &$teamId) {
    if (preg_match('/App\\\\Models\\\\Team";s:2:"id";i:(\d+)/', $command, $matches)) {
        $teamId = $matches[1];
        return true;
    }
    return false;
}

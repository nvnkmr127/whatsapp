<?php

namespace App\Console\Commands;

use App\Enums\AlertSeverity;
use App\Models\Campaign;
use App\Services\BroadcastService;
use App\Services\WhatsAppHealthMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and launch due scheduled campaigns';

    /**
     * Execute the console command.
     */
    /**
     * How long a due campaign may keep failing before we stop retrying it.
     *
     * This command runs every minute, so without a bound a campaign that cannot
     * launch (expired token, RED quality) is retried forever while the customer
     * sees it sitting in "scheduled" indefinitely. An hour is long enough for a
     * transient Meta error or a token refresh to resolve itself.
     */
    private const RETRY_GRACE_MINUTES = 60;

    public function handle(BroadcastService $service, WhatsAppHealthMonitor $monitor)
    {
        $this->info('Checking for scheduled campaigns...');

        $campaigns = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No due campaigns found.');

            return;
        }

        foreach ($campaigns as $campaign) {
            $this->info("Launching Campaign ID: {$campaign->id} - {$campaign->name}");
            try {
                // Determine Logic: 'launch' updates status to 'processing' immediately
                $service->launch($campaign);
                $this->info('Launched successfully.');
            } catch (\Exception $e) {
                $this->handleLaunchFailure($campaign, $e, $monitor);
            }
        }
    }

    /**
     * Retry inside the grace window, then give up loudly rather than silently
     * looping every minute for the rest of time.
     */
    private function handleLaunchFailure(Campaign $campaign, \Exception $e, WhatsAppHealthMonitor $monitor): void
    {
        $due = $campaign->scheduled_at ?? $campaign->created_at;
        $expired = $due && $due->lt(now()->subMinutes(self::RETRY_GRACE_MINUTES));

        if (! $expired) {
            Log::warning("Campaign {$campaign->id} launch failed, will retry: ".$e->getMessage());
            $this->warn("Campaign {$campaign->id} not ready, will retry.");

            return;
        }

        $campaign->update([
            'status' => 'failed',
            'error_message' => 'Could not launch within '.self::RETRY_GRACE_MINUTES.' minutes: '.$e->getMessage(),
        ]);

        Log::error("Campaign {$campaign->id} failed permanently: ".$e->getMessage());
        $this->error("Campaign {$campaign->id} marked failed after grace period.");

        // Surface it where the customer will actually see it, instead of only
        // in a log they do not read.
        try {
            $monitor->createAlert(
                $campaign->team,
                AlertSeverity::CRITICAL,
                'messaging',
                'campaign_launch_failed',
                "Campaign \"{$campaign->name}\" could not be sent: ".$e->getMessage(),
                ['campaign_id' => $campaign->id],
            );
        } catch (\Throwable $alertError) {
            // Never let alerting failure mask the original problem.
            Log::error('Failed to raise campaign failure alert: '.$alertError->getMessage());
        }
    }
}

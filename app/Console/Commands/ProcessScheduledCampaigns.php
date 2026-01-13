<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Services\BroadcastService;
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
    public function handle(BroadcastService $service)
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
                $this->info("Launched successfully.");
            } catch (\Exception $e) {
                Log::error("Failed to launch campaign {$campaign->id}: " . $e->getMessage());
                $this->error("Failed to launch campaign {$campaign->id}");
                // Mark as failed? Or retry? 
                // Currently launch() handles its own state mostly.
            }
        }
    }
}

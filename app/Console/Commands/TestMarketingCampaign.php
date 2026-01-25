<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Email\AppMarketingService;

class TestMarketingCampaign extends Command
{
    protected $signature = 'email:test-marketing {slug}';
    protected $description = 'Dispatch a marketing campaign to all opted-in users';

    public function handle(AppMarketingService $service)
    {
        $slug = $this->argument('slug');
        $this->info("Preparing to dispatch marketing campaign '{$slug}'...");

        if (!$this->confirm('Are you sure you want to blast all opted-in users?')) {
            return;
        }

        try {
            $count = $service->sendCampaign($slug);
            $this->info("Success! Campaign queued for {$count} users.");
        } catch (\Exception $e) {
            $this->error("Failed: " . $e->getMessage());
        }
    }
}

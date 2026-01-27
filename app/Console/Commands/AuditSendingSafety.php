<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendMessageJob;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;

class AuditSendingSafety extends Command
{
    protected $signature = 'audit:sending-safety';
    protected $description = 'Audit Sending Safety (Rate Limits, Parallel Execution)';

    public function handle()
    {
        $this->info('Starting Sending Safety Audit...');

        // 1. Check for Global/Team Rate Limit Configuration
        $this->info("\n1. Checking Rate Limit Configuration:");
        $hasThrottle = file_get_contents(app_path('Jobs/SendMessageJob.php'));

        if (str_contains($hasThrottle, 'RateLimiter::attempt')) {
            $this->info("PASS: RateLimiter usage detected in SendMessageJob.");
        } else {
            $this->error("FAIL: No 'RateLimiter::attempt' found in SendMessageJob.");
            $this->line("   - Risk: 20 concurrent workers can hit Meta API 20 times/sec, exceeding Tier limits (80 MPS is hard limit, but Tier 1k is strict on daily).");
        }

        // 2. Check Parallel Campaign Risk
        $this->info("\n2. Parallel Campaign Risk Analysis:");
        $producer = file_get_contents(app_path('Services/BroadcastEventProducer.php'));

        if (str_contains($producer, 'RateLimiter') || str_contains($producer, 'sleep')) {
            $this->info("PASS: Producer throttles event generation.");
        } else {
            $this->error("FAIL: BroadcastEventProducer dumps events as fast as DB reads.");
            $this->line("   - Scenario: User launches 5 campaigns of 10k contacts.");
            $this->line("   - Result: 50k jobs in queue. Workers process ASAP. Meta returns 131030.");
        }

        $this->info("\nAudit Complete.");
    }
}

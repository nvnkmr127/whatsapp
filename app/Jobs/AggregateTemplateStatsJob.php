<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\WhatsappTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Services\TemplateHealthService;

class AggregateTemplateStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * Aggregates stats from Campaigns into Templates.
     * Triggers Circuit Breaker check.
     */
    public function handle(TemplateHealthService $healthService): void
    {
        // We aggregate from Campaigns that have completed or are processing.
        // To avoid re-summing everything every time, we could:
        // 1. Only look at campaigns updated recently (incremental) -> Hard to manage state.
        // 2. Full re-calc (Expensive for large scale).
        // 3. Simple approach: Group by template_id on Campaigns table. 
        //    Campaign has sent_count, read_count.

        $stats = Campaign::query()
            ->select('template_id', DB::raw('SUM(sent_count) as total_sent'), DB::raw('SUM(read_count) as total_read'))
            ->whereNotNull('template_id')
            ->groupBy('template_id')
            ->get();

        foreach ($stats as $stat) {
            if (!$stat->template_id)
                continue;

            $template = WhatsappTemplate::find($stat->template_id);
            if ($template) {
                $template->updateQuietly([
                    'total_sent' => $stat->total_sent ?? 0,
                    'total_read' => $stat->total_read ?? 0,
                ]);

                // Trigger Circuit Breaker Check
                $healthService->checkCircuitBreaker($template);
            }
        }
    }
}

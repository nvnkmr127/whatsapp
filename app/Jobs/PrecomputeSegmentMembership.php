<?php

namespace App\Jobs;

use App\Models\Segment;
use App\Services\SegmentBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrecomputeSegmentMembership implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $segment;
    public $timeout = 3600; // 1 hour timeout for large segments

    /**
     * Create a new job instance.
     */
    public function __construct(Segment $segment)
    {
        $this->segment = $segment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting segment precomputation", ['segment_id' => $this->segment->id]);

        $startTime = microtime(true);

        // Clear existing memberships
        DB::table('segment_memberships')
            ->where('segment_id', $this->segment->id)
            ->delete();

        // Build query from segment rules
        $query = SegmentBuilder::buildQuery($this->segment->rules, $this->segment->team_id);

        $totalInserted = 0;

        // Insert in batches
        $query->chunk(1000, function ($contacts) use (&$totalInserted) {
            $memberships = $contacts->map(fn($contact) => [
                'segment_id' => $this->segment->id,
                'contact_id' => $contact->id,
                'added_at' => now(),
            ]);

            DB::table('segment_memberships')->insert($memberships->toArray());
            $totalInserted += $contacts->count();
        });

        // Update metadata
        $this->segment->update([
            'member_count' => $totalInserted,
            'last_computed_at' => now(),
        ]);

        $duration = round(microtime(true) - $startTime, 2);

        Log::info("Completed segment precomputation", [
            'segment_id' => $this->segment->id,
            'member_count' => $totalInserted,
            'duration_seconds' => $duration,
        ]);
    }
}

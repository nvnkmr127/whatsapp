<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BatchBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaignId;
    public $limit;
    public $offset;

    // Timeout for safety
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct($campaignId, $limit = 100, $offset = 0)
    {
        $this->campaignId = $campaignId;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = Campaign::find($this->campaignId);
        if (!$campaign || $campaign->status !== 'scheduled') {
            return; // Already processed or paused
        }

        // Fetch contacts for this batch (Simple implementation, usually based on segments)
        // For MVP we assume campaign has a relation to a segment or we filter contacts by team
        $contacts = Contact::where('team_id', $campaign->team_id)
            // ->whereIn('id', $segmentIds) // Future: Filter by segment
            ->skip($this->offset)
            ->take($this->limit)
            ->get();

        if ($contacts->isEmpty()) {
            // Check if we are done
            if ($this->offset > 0) {
                $campaign->update(['status' => 'completed', 'completed_at' => now()]);
            }
            return;
        }

        Log::info("Processing Broadcast Batch for Campaign {$this->campaignId} (Offset: {$this->offset}, Count: {$contacts->count()})");

        foreach ($contacts as $contact) {
            SendMessageJob::dispatch(
                $campaign->team_id,
                $contact->phone_number,
                'template',
                $campaign->template_variables ?? [],
                $campaign->template_name,
                $campaign->template_language ?? 'en_US'
            );
        }

        // Recursively dispatch next batch
        self::dispatch($this->campaignId, $this->limit, $this->offset + $this->limit)
            ->delay(now()->addSeconds(5)); // Rate limit protection
    }
}

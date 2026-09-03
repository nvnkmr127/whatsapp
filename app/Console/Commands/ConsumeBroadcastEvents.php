<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignMessageJob;
use App\Models\Campaign;
use App\Services\RateLimitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConsumeBroadcastEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:consume {--group=dispatchers} {--consumer=worker1} {--count=500} {--seconds=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume broadcast events from Redis Stream and dispatch jobs';

    protected $rateLimitService;

    protected $stream = 'whatsapp_broadcasts';

    protected ?string $traceId = null;

    public function __construct(RateLimitService $rateLimitService)
    {
        parent::__construct();
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $group = $this->option('group');
        $consumer = $this->option('consumer');
        $count = (int) $this->option('count');
        $maxSeconds = (int) $this->option('seconds');
        $startTime = microtime(true);
        $this->traceId = (string) \Illuminate\Support\Str::uuid();

        $this->info("Starting Broadcast Consumer: Group [{$group}], Consumer [{$consumer}]");

        // Database Polling
        while (true) {
            // Self-termination check for Scheduler
            if ($maxSeconds > 0 && (microtime(true) - $startTime) >= $maxSeconds) {
                $this->info("Time limit of {$maxSeconds} seconds reached. Exiting for scheduler.");
                break;
            }

            try {
                // Check if system is paused globally first
                if ($this->rateLimitService->isPaused()) {
                    $this->warn('Broadcasting is currently paused globally.');
                    sleep(5);

                    continue;
                }

                // --- FAIR-SHARE FETCHING ---
                // We want to avoid one tenant with 10k events blocking another with 1.
                // Fetch up to $count events, but interleave them in PHP if needed,
                // or just fetch by Team if there are many teams.

                $dbEvents = collect();
                $pendingTeams = \Illuminate\Support\Facades\DB::table('broadcast_events')
                    ->where('status', 'pending')
                    ->distinct()
                    ->pluck('team_id')
                    ->take(20); // Limit number of teams per cycle to avoid huge memory usage

                if ($pendingTeams->isNotEmpty()) {
                    $perTeamCount = max(1, ceil($count / ($pendingTeams->count() + 1)));

                    // 1. Process teams
                    foreach ($pendingTeams as $tId) {
                        if (is_null($tId)) {
                            continue;
                        }
                        $teamEvents = \Illuminate\Support\Facades\DB::table('broadcast_events')
                            ->where('status', 'pending')
                            ->where('team_id', $tId)
                            ->orderBy('id', 'asc')
                            ->limit($perTeamCount)
                            ->get();

                        $dbEvents = $dbEvents->concat($teamEvents);
                    }

                    // 2. Always check for null team events (fallbacks/system)
                    $nullEvents = \Illuminate\Support\Facades\DB::table('broadcast_events')
                        ->where('status', 'pending')
                        ->whereNull('team_id')
                        ->orderBy('id', 'asc')
                        ->limit($perTeamCount)
                        ->get();
                    $dbEvents = $dbEvents->concat($nullEvents);

                    // Shuffle or Interleave the collection
                    $dbEvents = $dbEvents->shuffle();
                } else {
                    // Fallback for events without team_id (legacy or system events)
                    $dbEvents = \Illuminate\Support\Facades\DB::table('broadcast_events')
                        ->where('status', 'pending')
                        ->whereNull('team_id')
                        ->orderBy('id', 'asc')
                        ->limit($count)
                        ->get();
                }

                if ($dbEvents->isNotEmpty()) {
                    foreach ($dbEvents as $event) {
                        // Check time limit inside the loop as well for responsiveness
                        if ($maxSeconds > 0 && (microtime(true) - $startTime) >= $maxSeconds) {
                            break 2; // Break both loops
                        }

                        // Optimistic Locking for DB events
                        $affected = \Illuminate\Support\Facades\DB::table('broadcast_events')
                            ->where('id', $event->id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'processing',
                                'group_name' => $group,
                                'locked_at' => now(),
                                'updated_at' => now(),
                            ]);

                        if ($affected) {
                            $data = [
                                'event_type' => $event->event_type,
                                'payload' => $event->payload,
                            ];
                            $this->processEvent((string) $event->id, $data, $group);
                        }
                    }

                    continue;
                }

                // C. Idle sleep
                sleep(2);

            } catch (\Exception $e) {
                $this->error('Consumer Loop Error: '.$e->getMessage());
                sleep(5);
            }
        }
    }

    protected function processEvent(string $id, array $data, string $group)
    {
        $eventType = $data['event_type'] ?? null;
        $payload = json_decode($data['payload'] ?? '{}', true);

        try {
            switch ($eventType) {
                case 'message.queued':
                    $this->processCampaignMessage($id, $payload);
                    break;

                case 'message.inbound':
                    Log::debug('BroadcastConsumer: Processing Inbound Message Event', ['event_id' => $id]);
                    \App\Jobs\PersistMessageJob::dispatch($payload)->onQueue('messages');
                    $this->info("Dispatched PersistMessageJob for Event {$id}");
                    $this->markDone($id);
                    break;

                case 'message.status':
                    \App\Jobs\UpdateMessageStatusJob::dispatch($payload)->onQueue('messages');
                    $this->info("Dispatched UpdateMessageStatusJob for Event {$id}");
                    $this->markDone($id);
                    break;

                default:
                    $this->warn("Unknown event type {$eventType} for event {$id}. Marking done to clear.");
                    $this->markDone($id);
                    break;
            }
        } catch (\Exception $e) {
            // Mark failed so it is visible and not retried forever
            $this->error("Failed to process event {$id}: ".$e->getMessage());
            \Illuminate\Support\Facades\DB::table('broadcast_events')
                ->where('id', $id)
                ->update(['status' => 'failed', 'updated_at' => now()]);
        }
    }

    protected function processCampaignMessage(string $id, array $payload)
    {
        $campaignId = $payload['campaign_id'] ?? null;
        $contactId = $payload['contact_id'] ?? null;
        $phoneNumber = $payload['phone_number'] ?? null;
        $teamId = $payload['meta']['team_id'] ?? 0;

        if (! $campaignId || ! $contactId || ! $phoneNumber) {
            $this->warn("Malformed campaign event {$id}, skipping.");
            $this->markDone($id);

            return;
        }

        // --- CAMPAIGN STATUS CHECK: don't send for cancelled/failed campaigns ---
        $campaignStatus = Cache::remember(
            "campaign_status:{$campaignId}",
            30,
            fn () => Campaign::find($campaignId)?->status
        );
        if (! $campaignStatus || in_array($campaignStatus, ['cancelled', 'failed'], true)) {
            $this->info("Campaign {$campaignId} is {$campaignStatus}, discarding event {$id}.");
            $this->markDone($id);

            return;
        }

        // --- PAUSE CHECK (Tenant Level) ---
        if ($this->rateLimitService->isPaused((int) $teamId)) {
            $this->release($id); // retry when unpaused

            return;
        }

        // --- RATE LIMIT CHECK (Provider Level) ---
        if (! $this->rateLimitService->canSend((int) $teamId, $phoneNumber)) {
            usleep(200000); // 200ms backoff
            if (! $this->rateLimitService->canSend((int) $teamId, $phoneNumber)) {
                $this->warn("Rate limit reached for provider/number {$phoneNumber}, backing off event {$id}");
                $this->release($id);

                return;
            }
        }

        // --- CAMPAIGN THROTTLE CHECK (Send Rate) ---
        $sendRate = $payload['meta']['send_rate'] ?? null;
        if (! $this->rateLimitService->canSendCampaign((int) $campaignId, $sendRate)) {
            $this->release($id);

            return;
        }

        // --- IDEMPOTENCY CHECK ---
        $lockKey = "broadcast_event_processed:{$id}";
        if (! Cache::add($lockKey, true, 3600)) {
            $this->info("Event {$id} already processed, skipping.");
            $this->markDone($id);

            return;
        }

        try {
            $snapshotId = $payload['snapshot_id'] ?? null;
            SendCampaignMessageJob::dispatch($campaignId, $contactId, $this->traceId, $snapshotId)->onQueue('broadcasts');
            $this->info("Dispatched Job for Campaign {$campaignId}, Contact {$contactId} (Event: {$id}, Snapshot: {$snapshotId})");
            $this->markDone($id);
        } catch (\Exception $e) {
            $this->error("Failed to process event {$id}: ".$e->getMessage());
            Cache::forget($lockKey);
            throw $e; // Rethrow to be caught by processEvent
        }
    }

    /** Mark an event fully handled. */
    protected function markDone(string $id): void
    {
        \Illuminate\Support\Facades\DB::table('broadcast_events')
            ->where('id', $id)
            ->update(['status' => 'processed', 'updated_at' => now()]);
    }

    /** Put an event back in the pending pool to retry later. */
    protected function release(string $id): void
    {
        \Illuminate\Support\Facades\DB::table('broadcast_events')
            ->where('id', $id)
            ->update(['status' => 'pending', 'updated_at' => now()]);

        usleep(50000); // 50ms backoff to prevent tight CPU spinlock during rate limits
    }
}

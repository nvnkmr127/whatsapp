<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignSnapshot;
use App\Models\Contact;
use App\Jobs\SendCampaignMessageJob;
use App\Services\EventBusService;
use App\Services\RateLimitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ConsumeBroadcastEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:consume {--group=dispatchers} {--consumer=worker1} {--count=50} {--seconds=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume broadcast events from Redis Stream and dispatch jobs';

    protected $eventBus;
    protected $rateLimitService;
    protected $stream = 'whatsapp_broadcasts';

    public function __construct(EventBusService $eventBus, RateLimitService $rateLimitService)
    {
        parent::__construct();
        $this->eventBus = $eventBus;
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
                    $this->warn("Broadcasting is currently paused globally.");
                    sleep(5);
                    continue;
                }

                $dbEvents = \Illuminate\Support\Facades\DB::table('broadcast_events')
                    ->where('status', 'pending')
                    ->orderBy('id', 'asc')
                    ->limit($count)
                    ->get();

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
                                'updated_at' => now()
                            ]);

                        if ($affected) {
                            $data = [
                                'event_type' => $event->event_type,
                                'payload' => $event->payload
                            ];
                            $this->processEvent((string) $event->id, $data, $group);
                        }
                    }
                    continue;
                }

                // C. Idle sleep
                sleep(2);

            } catch (\Exception $e) {
                $this->error("Consumer Loop Error: " . $e->getMessage());
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
                    $this->processCampaignMessage($id, $payload, $group);
                    break;

                case 'message.inbound':
                    // Dispatch Job to persist inbound message
                    \App\Jobs\PersistMessageJob::dispatch($payload)->onQueue('messages');
                    $this->info("Dispatched PersistMessageJob for Event {$id}");
                    $this->eventBus->ack($this->stream, $group, [$id]);
                    break;

                case 'message.status':
                    // Dispatch Job to update message status and campaign stats
                    \App\Jobs\UpdateMessageStatusJob::dispatch($payload)->onQueue('messages');
                    $this->info("Dispatched UpdateMessageStatusJob for Event {$id}");
                    $this->eventBus->ack($this->stream, $group, [$id]);
                    break;

                default:
                    $this->warn("Unknown event type {$eventType} for event {$id}. Acking to clear.");
                    $this->eventBus->ack($this->stream, $group, [$id]);
                    break;
            }
        } catch (\Exception $e) {
            $this->error("Failed to process event {$id}: " . $e->getMessage());
            // Optionally release lock instead of acking if we want retry, 
            // but for now we rely on the loop's error handling to retry naturally if it crashes, 
            // or better, if it's a logic error, we shouldn't infinite loop. 
            // We'll leave it in 'processing' state (locked) to be retried or fixed manually?
            // Actually, safe bet for now is to LOG and maybe move to failed_events table later.
            // For this implementation, we will NOT ack, so it stays 'processing'.
        }
    }

    protected function processCampaignMessage(string $id, array $payload, string $group)
    {
        $campaignId = $payload['campaign_id'] ?? null;
        $contactId = $payload['contact_id'] ?? null;
        $phoneNumber = $payload['phone_number'] ?? null;
        $teamId = $payload['meta']['team_id'] ?? 0;

        if (!$campaignId || !$contactId || !$phoneNumber) {
            $this->warn("Malformed campaign event {$id}, skipping.");
            $this->eventBus->ack($this->stream, $group, [$id]);
            return;
        }

        // --- PAUSE CHECK (Tenant Level) ---
        if ($this->rateLimitService->isPaused((int) $teamId)) {
            return; // Leave in PEL to retry later when unpaused
        }

        // --- RATE LIMIT CHECK ---
        if (!$this->rateLimitService->canSend((int) $teamId, $phoneNumber)) {
            usleep(200000);
            if (!$this->rateLimitService->canSend((int) $teamId, $phoneNumber)) {
                $this->warn("Rate limit reached for {$phoneNumber}, backing off event {$id}");
                return;
            }
        }

        // --- IDEMPOTENCY CHECK ---
        $lockKey = "broadcast_event_processed:{$id}";
        if (!Cache::add($lockKey, true, 3600)) {
            $this->info("Event {$id} already processed, skipping.");
            $this->eventBus->ack($this->stream, $group, [$id]);
            return;
        }

        try {
            SendCampaignMessageJob::dispatch($campaignId, $contactId)->onQueue('broadcasts');
            $this->info("Dispatched Job for Campaign {$campaignId}, Contact {$contactId} (Event: {$id})");
            $this->eventBus->ack($this->stream, $group, [$id]);
        } catch (\Exception $e) {
            $this->error("Failed to process event {$id}: " . $e->getMessage());
            Cache::forget($lockKey);
            throw $e; // Rethrow to be caught by main loop
        }
    }
}

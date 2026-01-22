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
    protected $signature = 'broadcast:consume {--group=dispatchers} {--consumer=worker1} {--count=50}';

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

        $this->info("Starting Broadcast Consumer: Group [{$group}], Consumer [{$consumer}]");

        // Database Polling
        while (true) {
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

        if ($eventType !== 'message.queued') {
            $this->eventBus->ack($this->stream, $group, [$id]);
            return;
        }

        $campaignId = $payload['campaign_id'] ?? null;
        $contactId = $payload['contact_id'] ?? null;
        $phoneNumber = $payload['phone_number'] ?? null;
        $teamId = $payload['meta']['team_id'] ?? 0; // Assuming team_id is in meta

        if (!$campaignId || !$contactId || !$phoneNumber) {
            $this->warn("Malformed event {$id}, skipping.");
            $this->eventBus->ack($this->stream, $group, [$id]);
            return;
        }

        // --- PAUSE CHECK (Tenant Level) ---
        if ($this->rateLimitService->isPaused((int) $teamId)) {
            return; // Leave in PEL to retry later when unpaused
        }

        // --- RATE LIMIT CHECK ---
        if (!$this->rateLimitService->canSend((int) $teamId, $phoneNumber)) {
            // If limit reached, we don't ACK. We can either BLOCK the loop (not ideal)
            // or let the message remain in the PEL for another consumer or a retry.
            // For now, we'll wait 200ms and retry internally once before giving up
            // this specific event to keep the consumer group moving.
            usleep(200000);
            if (!$this->rateLimitService->canSend((int) $teamId, $phoneNumber)) {
                $this->warn("Rate limit reached for {$phoneNumber}, backing off event {$id}");
                return; // Return without Ack means it stays in the PEL (Pending Entry List)
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
        }
    }
}

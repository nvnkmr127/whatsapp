<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EventBusService;
use App\Jobs\PersistMessageJob;
use Illuminate\Support\Facades\Log;

class ConsumeWhatsAppEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:consume-events {group=api-workers} {consumer=worker-1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume WhatsApp events from Redis Stream and dispatch handlers.';

    /**
     * Execute the console command.
     */
    public function handle(EventBusService $eventBus)
    {
        $stream = 'whatsapp_events';
        $group = $this->argument('group');
        $consumer = $this->argument('consumer');

        $this->info("Starting Consumer for stream: {$stream}");

        // Ensure group exists
        $eventBus->createGroup($stream, $group);

        while (true) {
            try {
                // XREADGROUP GROUP group consumer BLOCK 2000 COUNT 10 STREAMS stream >
                // Using raw Redis Facade logic inside Service or here? 
                // Service has 'consume' wrapper? No, I defined `consume` but likely need customization here.

                // Let's us raw Redis here for clarity or add method to service?
                // I'll add logic here using Facade for control.

                $entries = \Illuminate\Support\Facades\Redis::xreadgroup(
                    'GROUP',
                    $group,
                    $consumer,
                    'BLOCK',
                    2000,
                    'COUNT',
                    10,
                    'STREAMS',
                    $stream,
                    '>'
                );

                if (!$entries) {
                    continue;
                }

                foreach ($entries[$stream] as $id => $data) {
                    $this->processEvent($id, $data);
                    $eventBus->ack($stream, $group, [$id]);
                }

            } catch (\Exception $e) {
                Log::error("Consumer Error: " . $e->getMessage());
                sleep(1);
            }
        }
    }

    protected function processEvent($id, $data)
    {
        $type = $data['event_type'] ?? null;
        $payload = json_decode($data['payload'] ?? '{}', true);

        Log::info("Processing Event: {$type} (ID: {$id})");

        if ($type === 'message.inbound') {
            // Dispatch Persistence Job
            PersistMessageJob::dispatch($payload);
        }

        // Handle other types...
    }
}

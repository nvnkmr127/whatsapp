<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Message;
use App\Models\Team;
use App\Models\WebhookPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payloadId;

    /**
     * Create a new job instance.
     */
    public function __construct($payloadId)
    {
        $this->payloadId = $payloadId;
        $this->onQueue('webhooks');
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = [10, 60, 300];


    /**
     * Execute the job.
     */
    /**
     * Execute the job.
     */
    public function handle(\App\Services\EventBusService $eventBus): void
    {
        Log::info("ProcessWebhookJob (Stream Producer) started for Payload ID: {$this->payloadId}");
        $payloadRecord = WebhookPayload::find($this->payloadId);

        if (!$payloadRecord) {
            Log::error("Webhook Payload not found: {$this->payloadId}");
            return;
        }

        $payloadRecord->update(['status' => 'processing']);

        try {
            $body = $payloadRecord->payload;

            // Normalize payload
            if (is_string($body)) {
                $body = json_decode($body, true);
            }

            if (empty($body['entry'][0]['changes'][0]['value'])) {
                $payloadRecord->update(['status' => 'processed', 'error_message' => 'No changes found']);
                return;
            }

            $change = $body['entry'][0]['changes'][0]['value'];

            // 1. Handle Messages (Inbound)
            if (isset($change['messages']) && is_array($change['messages'])) {
                // Construct standardized event
                $event = \App\Factories\EventFactory::makeInboundMessage($body);

                // Idempotency: Redis SETNX would happen here or inside EventBus, but we rely on DB unique key downstream for now too.
                // We'll publish to the stream.

                $id = $eventBus->publish('whatsapp_events', 'message.inbound', $event['payload']);

                if ($id) {
                    Log::info("Published Inbound Event: {$id}");
                }
            }

            // 2. Handle Status Updates
            if (isset($change['statuses']) && is_array($change['statuses'])) {
                foreach ($change['statuses'] as $statusData) {
                    $eventBus->publish('whatsapp_events', 'message.status', [
                        'provider_message_id' => $statusData['id'],
                        'status' => $statusData['status'],
                        'timestamp' => $statusData['timestamp'] ?? time(),
                        'details' => $statusData
                    ]);
                }
            }

            $payloadRecord->update(['status' => 'processed']);

        } catch (\Exception $e) {
            Log::error("Webhook Producer Failed: " . $e->getMessage());
            $payloadRecord->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessWebhookJob (Producer) FAILED: " . $exception->getMessage());
    }

}

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

    protected $payloadId;
    public $traceId;

    /**
     * Create a new job instance.
     */
    public function __construct($payloadId, $traceId = null)
    {
        $this->payloadId = $payloadId;
        $this->traceId = $traceId;
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
    public function handle(\App\Services\EventBusService $eventBus): void
    {
        // Restore Trace Context
        if ($this->traceId) {
            \App\Services\TraceContext::set($this->traceId);
        } else {
            \App\Services\TraceContext::ensureTraceId();
        }

        Log::info("ProcessWebhookJob (Stream Producer) started for Payload ID: {$this->payloadId} [Trace: " . \App\Services\TraceContext::getTraceId() . "]");
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
            $metadata = $change['metadata'] ?? [];
            $phoneId = $metadata['phone_number_id'] ?? null;
            $teamId = null;

            if ($phoneId) {
                $teamId = \Illuminate\Support\Facades\Cache::remember("team_id_by_phone_id:{$phoneId}", 3600, function () use ($phoneId) {
                    return Team::where('whatsapp_phone_number_id', $phoneId)->value('id');
                });
            }

            // 1. Handle Messages (Inbound)
            if (isset($change['messages']) && is_array($change['messages'])) {
                foreach ($change['messages'] as $messageData) {
                    $wamid = $messageData['id'] ?? null;

                    // Deduplication Check
                    if ($wamid && Message::where('whatsapp_message_id', $wamid)->exists()) {
                        Log::info("Duplicate Message Ignored: {$wamid}");
                        $payloadRecord->update(['status' => 'processed', 'error_message' => "Duplicate: {$wamid}"]);
                        return;
                    }

                    // Construct standardized event
                    $event = \App\Factories\EventFactory::makeInboundMessage($body);

                    // Publish to stream
                    $id = $eventBus->publish('whatsapp_events', 'message.inbound', $event['payload'], $teamId);

                    if (!$id) {
                        throw new \Exception("EventBus failed to publish Inbound Message Event");
                    }

                    Log::info("Published Inbound Event: {$id}");
                }
            }

            // 2. Handle Status Updates
            if (isset($change['statuses']) && is_array($change['statuses'])) {
                foreach ($change['statuses'] as $statusData) {
                    $wamid = $statusData['id'] ?? null;
                    $newStatus = $statusData['status'] ?? null;

                    // Optional: Deduplicate status updates if needed, but usually idempotent updates are fine.
                    // We can check if the message is already in that status to save DB writes, but it's optimization, not critical reliability.

                    $id = $eventBus->publish('whatsapp_events', 'message.status', [
                        'provider_message_id' => $statusData['id'],
                        'status' => $statusData['status'],
                        'timestamp' => $statusData['timestamp'] ?? time(),
                        'details' => $statusData
                    ], $teamId);

                    if (!$id) {
                        throw new \Exception("EventBus failed to publish Status Event for {$wamid}");
                    }
                }
            }

            // 3. Handle Template Status Updates
            if (($change['field'] ?? '') === 'message_template_status_update') {
                $tId = $change['message_template_id'] ?? null;
                $newStatus = $change['event'] ?? null; // APPROVED, REJECTED, PAUSED, FLAGGED, DISABLED

                if ($tId && $newStatus) {
                    $tpl = \App\Models\WhatsappTemplate::where('whatsapp_template_id', $tId)->first();

                    if ($tpl) {
                        $tpl->update(['status' => $newStatus]);

                        if ($newStatus === 'FLAGGED' || $newStatus === 'DISABLED') {
                            Log::critical("Template {$tpl->name} ({$tId}) is {$newStatus}. Immediate attention required.");
                            // Future: Dispatch alert notification
                        } else {
                            Log::info("Template {$tpl->name} status updated to {$newStatus}.");
                        }
                    } else {
                        Log::warning("Received status update [{$newStatus}] for unknown Template ID: {$tId}. Sync may be required.");
                    }
                }
            }

            // 4. Handle Account Updates (and Quality Updates)
            if (($change['field'] ?? '') === 'phone_number_quality_update') {
                $status = $change['new_status'] ?? 'UNKNOWN'; // APPROVED, FLAGGED, RESTRICTED, UNKNOWN
                $quality = $change['new_quality_score'] ?? 'UNKNOWN'; // GREEN, YELLOW, RED

                Log::info("WhatsApp Quality Update: Status={$status}, Quality={$quality}", ['payload' => $change]);

                // Circuit Breaker: Pause everything if FLAGGED or RESTRICTED or RED
                if (in_array($status, ['FLAGGED', 'RESTRICTED']) || $quality === 'RED') {
                    // Log Critical and Attempt Pause if we can resolve Team.
                    Log::critical("CRITICAL: WhatsApp Account Risk! Status: {$status}. IMMEDIATE ACTION REQUIRED.");

                    // Dispatch Risk Event
                    \App\Events\WhatsAppAccountRisk::dispatch('QUALITY_UPDATE', $change, null); // TeamID null for now unless resolved
                }
            }

            // 5. Handle Calls
            if (isset($change['calls']) && is_array($change['calls'])) {
                if ($teamId) {
                    $team = Team::find($teamId);
                    if ($team) {
                        $callProcessor = new \App\Services\WhatsAppCallProcessor();
                        $callProcessor->process($team, $change['calls']);
                    }
                } else {
                    Log::warning("Received call event but couldn't resolve Team.", ['phone_id' => $phoneId]);
                }
            }

            if (($change['field'] ?? '') === 'account_update' || ($change['field'] ?? '') === 'account_settings_update') {
                Log::info("WhatsApp Account Update Received", ['payload' => $change]);
                // Dispatch event for system to react (e.g., update local DB)
                \App\Events\WhatsAppAccountUpdated::dispatch($change);
            }

            $payloadRecord->update(['status' => 'processed']);

        } catch (\Exception $e) {
            Log::error("Webhook Producer Failed: " . $e->getMessage());
            // Do NOT update to 'failed' here if we want to retry!
            // But if we throw, Laravel will handle the retry. 
            // We should record the attempt error in the payload though? 
            // Actually, if we throw, the job is released back to queue.
            // We can update the status to 'retrying' or similar if we want, but 'processing' is fine until it finally fails.

            // However, the catch block in original code swallowed the error (after logging) AND updated status to failed.
            // This prevented retry.
            // We MUST throw $e to trigger retry.
            // And we probably shouldn't set checks to 'failed' yet unless final attempt.

            // Let's update `error_message` but keep status 'processing' or 'pending' maybe?
            $payloadRecord->update(['error_message' => $e->getMessage()]); // Keep trace

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

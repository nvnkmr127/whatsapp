<?php

namespace App\Jobs;

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
    use \App\Traits\ChecksTenantMaintenanceMode;
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

        Log::info("ProcessWebhookJob (Producer) started for Payload ID: {$this->payloadId}");
        $payloadRecord = WebhookPayload::find($this->payloadId);

        if (! $payloadRecord) {
            return;
        }

        $payloadRecord->update(['status' => 'processing']);

        try {
            $body = $payloadRecord->payload;
            if (is_string($body)) {
                $body = json_decode($body, true);
            }

            if (empty($body['entry'][0]['changes'][0]['value'])) {
                $payloadRecord->update(['status' => 'processed']);

                return;
            }

            $change = $body['entry'][0]['changes'][0]['value'];

            // [PROD-HARDENING] Idempotency / De-duplication
            // Detect if this specific event (Message ID or Status ID) has been processed
            $eventId = $change['messages'][0]['id'] ?? $change['statuses'][0]['id'] ?? null;
            if ($eventId) {
                $cacheKey = "whatsapp_processed_event:{$eventId}";
                if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    Log::info("WhatsApp Webhook: Skipping duplicate event {$eventId} for Payload {$this->payloadId}");
                    $payloadRecord->update(['status' => 'processed', 'error_message' => 'SKIPPED_DUPLICATE']);
                    return;
                }
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, 86400); // 24 hour TTL
            }

            $metadata = $change['metadata'] ?? [];
            $phoneId = $metadata['phone_number_id'] ?? null;
            $teamId = $this->resolveTeamId($body, $change, $phoneId);

            if ($teamId && $this->isTeamUnderMaintenance($teamId, 'release', 60)) {
                return;
            }

            if (! $teamId) {
                // Handle retry for unresolved team
                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff[$this->attempts() - 1] ?? 60);

                    return;
                }
                $payloadRecord->update(['status' => 'failed', 'error_message' => 'Team ID resolution failed']);

                return;
            }

            // Router Pattern: Delegate all event handling logic to the Router
            $router = new \App\Core\Webhooks\WhatsAppEventRouter($eventBus, $teamId, $phoneId);
            $router->route($change, $body);

            $payloadRecord->update(['status' => 'processed']);

        } catch (\Exception $e) {
            Log::error('Webhook Producer Failed: '.$e->getMessage());
            $payloadRecord->update(['error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function resolveTeamId(array $body, array $change, ?string $phoneId): ?int
    {
        $resolve = function ($col, $val) {
            $key = "team_id_by_{$col}:{$val}";

            return \Illuminate\Support\Facades\Cache::remember($key, 3600, function () use ($col, $val) {
                return Team::where($col, $val)->value('id');
            });
        };

        if ($phoneId) {
            return $resolve('whatsapp_phone_number_id', $phoneId);
        }

        $entryId = $body['entry'][0]['id'] ?? null;
        if ($entryId) {
            return $resolve('whatsapp_business_account_id', $entryId);
        }

        $wabaId = $change['waba_id'] ?? $change['waba_info']['waba_id'] ?? null;
        if ($wabaId) {
            return $resolve('whatsapp_business_account_id', $wabaId);
        }

        return null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessWebhookJob FAILED for Payload ID {$this->payloadId}");
        WebhookPayload::where('id', $this->payloadId)->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}

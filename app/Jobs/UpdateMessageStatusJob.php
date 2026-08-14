<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateMessageStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public $traceId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 2;

    /**
     * Create a new job instance.
     *
     * @param  array  $data  ['provider_message_id', 'status', 'timestamp', 'details']
     */
    public function __construct(array $data, ?string $traceId = null)
    {
        $this->data = $data;
        $this->traceId = $traceId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Restore Trace Context
        if ($this->traceId) {
            \App\Services\TraceContext::set($this->traceId);
        } else {
            \App\Services\TraceContext::ensureTraceId();
        }

        $providerMessageId = $this->data['provider_message_id'];
        $status = $this->data['status'];
        $timestamp = $this->data['timestamp'] ?? time();
        $details = $this->data['details'] ?? [];

        Log::info("UpdateMessageStatusJob: Processing status '{$status}' for message '{$providerMessageId}'");

        $message = Message::where('whatsapp_message_id', $providerMessageId)->first();

        if (! $message) {
            // Race condition check: If webhook arrives before SendMessageJob saves the ID
            if ($this->attempts() < $this->tries) {
                Log::warning("UpdateMessageStatusJob: Message not found for provider ID: {$providerMessageId}. Retrying in 2s...");
                $this->release(2);

                return;
            }

            Log::warning("UpdateMessageStatusJob: Message not found for provider ID: {$providerMessageId} after {$this->tries} attempts. Giving up.");

            return;
        }

        $oldStatus = $message->status;

        // --- STATUS STICKINESS GUARD (UC-SAFE-01) ---
        // WhatsApp webhooks are not guaranteed to be FIFO. We must prevent
        // backward transitions (e.g., 'read' -> 'delivered').
        $statusRanks = [
            'queued' => 0,
            'sent' => 1,
            'delivered' => 2,
            'read' => 3,
            'failed' => 4, // Terminal
        ];

        $currentRank = $statusRanks[$oldStatus] ?? 0;
        $newRank = $statusRanks[$status] ?? 0;

        // Rule: Never move backward unless fixing a 'failed' state (rare)
        // or if the new status is 'failed' (can happen after 'sent')
        if ($newRank < $currentRank && $status !== 'failed') {
            Log::info("UpdateMessageStatusJob: Ignoring backward status transition from '{$oldStatus}' to '{$status}' for message '{$providerMessageId}'");

            return;
        }

        $updateData = [
            'status' => $status,
            'updated_at' => now(),
        ];

        $eventTime = Carbon::createFromTimestamp($timestamp);

        // --- TIMESTAMP GUARDS ---
        // Only set timestamps if they haven't been set yet (prevents overwriting original event times)
        if ($status === 'delivered' && empty($message->delivered_at)) {
            $updateData['delivered_at'] = $eventTime;
        }

        if ($status === 'read') {
            if (empty($message->read_at)) {
                $updateData['read_at'] = $eventTime;
            }
            // Auto-fill delivered if read came first
            if (empty($message->delivered_at)) {
                $updateData['delivered_at'] = $eventTime;
            }
        }

        if ($status === 'failed') {
            $error = $details['errors'][0] ?? [];
            $updateData['error_message'] = $error['message'] ?? 'WhatsApp API Error';
            Log::warning("UpdateMessageStatusJob: Delivery failed for '{$providerMessageId}'", [
                'code' => $error['code'] ?? null,
                'title' => $error['title'] ?? null,
                'message' => $updateData['error_message'],
            ]);
        }

        $message->update($updateData);

        // T11: Automation Trigger for campaign message read
        if ($status === 'read' && $message->campaign_id && $message->contact) {
            try {
                if (class_exists(\App\Services\AutomationService::class)) {
                    app(\App\Services\AutomationService::class)->checkSpecialTriggers($message->contact, 'campaign_message_read', [
                        'campaign_id' => $message->campaign_id,
                        'message_id' => $message->id,
                        'template_id' => $message->template_id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::debug('T11 Trigger failed (Ignored): '.$e->getMessage());
            }
        }

        // Broadcast the update for UI
        \App\Events\MessageStatusUpdated::dispatch($message);

        // Update Campaign Stats if applicable
        if ($message->campaign_id) {
            $this->updateCampaignStats($message, $oldStatus, $status);
        }
    }

    protected function updateCampaignStats(Message $message, $oldStatus, $newStatus)
    {
        $campaign = Campaign::find($message->campaign_id);
        if (! $campaign) {
            return;
        }

        // 1. Sync individual CampaignDetail row status
        // We use phone number and campaign_id to find the matching detail
        \App\Models\CampaignDetail::where('campaign_id', $message->campaign_id)
            ->where('phone', $message->contact->phone_number ?? $message->to)
            ->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

        // 2. Logic to prevent double counting on Campaign totals
        // We only increment if it's the first time reaching this state
        if ($newStatus === 'delivered' || $newStatus === 'read') {
            // If it wasn't delivered or read before, increment del_count
            if (! in_array($oldStatus, ['delivered', 'read'])) {
                $campaign->increment('del_count');
            }
        }

        if ($newStatus === 'read') {
            // If it wasn't read before, increment read_count
            if ($oldStatus !== 'read') {
                $campaign->increment('read_count');
            }
        }

        // Broadcast for live UI updates if listening
        \App\Events\CampaignProgressUpdated::dispatch($campaign);
    }
}

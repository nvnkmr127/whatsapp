<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateMessageStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @param array $data ['provider_message_id', 'status', 'timestamp', 'details']
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $providerMessageId = $this->data['provider_message_id'];
        $status = $this->data['status'];
        $timestamp = $this->data['timestamp'] ?? time();
        $details = $this->data['details'] ?? [];

        Log::info("UpdateMessageStatusJob: Processing status '{$status}' for message '{$providerMessageId}'");

        $message = Message::where('whatsapp_message_id', $providerMessageId)->first();

        if (!$message) {
            Log::warning("UpdateMessageStatusJob: Message not found for provider ID: {$providerMessageId}");
            return;
        }

        $oldStatus = $message->status;

        // Map WhatsApp status to our internal status
        // WhatsApp statuses: sent, delivered, read, failed, deleted
        $newStatus = $status;
        if ($status === 'read') {
            $newStatus = 'read';
        } elseif ($status === 'delivered') {
            $newStatus = 'delivered';
        } elseif ($status === 'failed') {
            $newStatus = 'failed';
        }

        $updateData = [
            'status' => $newStatus,
            'updated_at' => now(),
        ];

        $eventTime = Carbon::createFromTimestamp($timestamp);

        if ($status === 'delivered' && empty($message->delivered_at)) {
            $updateData['delivered_at'] = $eventTime;
        }

        if ($status === 'read') {
            if (empty($message->read_at)) {
                $updateData['read_at'] = $eventTime;
            }
            if (empty($message->delivered_at)) {
                $updateData['delivered_at'] = $eventTime;
            }
        }

        if ($status === 'failed') {
            $updateData['error_message'] = $details['errors'][0]['message'] ?? 'WhatsApp API Error';
        }

        $message->update($updateData);

        // Broadcast the update for UI
        \App\Events\MessageStatusUpdated::dispatch($message);

        // Update Campaign Stats if applicable
        if ($message->campaign_id) {
            $this->updateCampaignStats($message, $oldStatus, $newStatus);
        }
    }

    protected function updateCampaignStats(Message $message, $oldStatus, $newStatus)
    {
        $campaign = Campaign::find($message->campaign_id);
        if (!$campaign)
            return;

        // 1. Sync individual CampaignDetail row status
        // We use phone number and campaign_id to find the matching detail
        \App\Models\CampaignDetail::where('campaign_id', $message->campaign_id)
            ->where('phone', $message->contact->phone ?? $message->to)
            ->update([
                'status' => $newStatus,
                'updated_at' => now()
            ]);

        // 2. Logic to prevent double counting on Campaign totals
        // We only increment if it's the first time reaching this state
        if ($newStatus === 'delivered' || $newStatus === 'read') {
            // If it wasn't delivered or read before, increment del_count
            if (!in_array($oldStatus, ['delivered', 'read'])) {
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

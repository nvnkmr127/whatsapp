<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class PersistMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $eventPayload;
    public $traceId;

    /**
     * Create a new job instance.
     * @param array $eventPayload The standardized event payload from the Event Bus.
     * @param string|null $traceId The correlation ID for tracing.
     */
    public function __construct(array $eventPayload, ?string $traceId = null)
    {
        $this->eventPayload = $eventPayload;
        $this->traceId = $traceId;
        $this->onQueue('messages'); // Persist on the message queue or a separate 'persistence' queue
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

        $data = $this->eventPayload;
        Log::info("PersistMessageJob: Starting for Message ID: " . ($data['provider_id'] ?? 'unknown'));

        // extract vital IDs
        $phoneId = $data['to_phone_id'];
        $team = Team::where('whatsapp_phone_number_id', $phoneId)->first();

        // Fallback: Resolve via WABA ID
        if (!$team) {
            $wabaId = $data['waba_id'] ?? null;
            if ($wabaId) {
                $team = Team::where('whatsapp_business_account_id', $wabaId)->first();
            }
        }

        Log::debug("PersistMessageJob: Team Resolution", [
            'attempted_phone_id' => $phoneId,
            'attempted_waba_id' => $data['waba_id'] ?? 'N/A',
            'team_found' => $team ? $team->id : 'NONE'
        ]);

        if (!$team) {
            Log::error("PersistMessageJob: Team not found for Phone ID: {$phoneId} or WABA ID: " . ($data['waba_id'] ?? 'N/A'));
            return;
        }

        $lock = \Illuminate\Support\Facades\Cache::lock("persist_message_{$data['provider_id']}", 10);
        try {
            if (!$lock->get()) {
                Log::info("PersistMessageJob: Lock already held for message: " . $data['provider_id']);
                return;
            }

            // 1. Idempotency: Double check DB
            if (Message::where('whatsapp_message_id', $data['provider_id'])->exists()) {
                Log::info("PersistMessageJob: Duplicate message ignored: " . $data['provider_id']);
                return;
            }

        // 1. Contact Management (thread-safe)
        $phone = \App\Helpers\PhoneNumberHelper::normalize($data['from_phone']);
        $name = $data['contact_name'] ?? null;

        $contactService = new \App\Services\ContactService();
        $contact = $contactService->createOrUpdate([
            'team_id' => $team->id,
            'phone_number' => $phone,
            'name' => $name,
        ]);

        // 2. Update interaction timestamps atomically
        // Use server time to ensure consistency with PolicyService checks
        $messageTimestamp = now();

        // Log the update attempt for debugging
        Log::info("PersistMessageJob: Updating contact {$contact->id} timestamp to {$messageTimestamp}");

        \App\Models\Contact::where('id', $contact->id)->update([
            'last_interaction_at' => $messageTimestamp,
            'last_customer_message_at' => $messageTimestamp,
        ]);

        // 2. Keyword Management
        $content = $this->extractContent($data['content']);
        $cleanContent = strtoupper(trim($content));

        if ($cleanContent === 'STOP') {
            (new \App\Services\ConsentService())->optOut($contact);
        } elseif ($cleanContent === 'START') {
            (new \App\Services\ConsentService())->optIn($contact, 'START_KEYWORD');
        }

        // 3. Conversation Management
        $conversationService = new \App\Services\ConversationService();
        $conversation = $conversationService->ensureActiveConversation($contact);
        $conversationService->handleIncomingMessage($conversation);

        // 4. Media Handling
        $mediaId = null;
        $mediaType = null;
        $caption = null;
        $msgData = $data['content'];

        if (in_array($msgData['type'], ['image', 'video', 'audio', 'document', 'sticker'])) {
            $type = $msgData['type'];
            $mediaItem = $msgData[$type];

            $mediaId = $mediaItem['id'] ?? null;
            $caption = $mediaItem['caption'] ?? null;
            $mediaType = $mediaItem['mime_type'] ?? null;
        }

        // 5. Campaign Attribution Logic
        $attributedCampaignId = null;
        $msgData = $data['content'];

        // A. Direct Attribution (WhatsApp Reply Context)
        if (isset($msgData['context']['id'])) {
            $originalMessageId = $msgData['context']['id'];
            $originalMessage = Message::where('whatsapp_message_id', $originalMessageId)->first();
            if ($originalMessage && $originalMessage->campaign_id) {
                $attributedCampaignId = $originalMessage->campaign_id;
                Log::info("PersistMessageJob: Direct attribution via Context ID for Campaign: {$attributedCampaignId}");
            }
        }

        // B. Temporal Attribution Fallback (Redis/Cache Pointer)
        if (!$attributedCampaignId) {
            $phone = $data['from_phone']; // Standardize phone for lookup
            $attributedCampaignId = \Illuminate\Support\Facades\Cache::get("last_campaign:contact:{$phone}");

            if ($attributedCampaignId) {
                Log::info("PersistMessageJob: Temporal attribution via Cache/Redis for Campaign: {$attributedCampaignId}");
            }
        }

        // 5.b QR Code Lead Capture Tracking
        if ($content && preg_match('/\[ref:([a-zA-Z0-9-]+)\]/', $content, $matches)) {
            $slug = $matches[1];
            $widget = \App\Models\LeadCaptureWidget::where('slug', $slug)->first();
            if ($widget) {
                $widget->increment('conversion_count');

                // Track as Lead Source if available
                $leadSource = \App\Models\LeadSource::firstOrCreate(
                    ['team_id' => $team->id, 'name' => "QR: " . $widget->name],
                    ['type' => 'custom']
                );

                if (!$contact->lead_source_id) {
                    $contact->update(['lead_source_id' => $leadSource->id]);
                }

                Log::info("Lead Capture Conversion tracked for Widget: {$widget->name}, Slug: {$slug}");
            }
        }

        // 6. Create Message Record
        $meta = $msgData;
        if (isset($data['referral'])) {
            $meta['referral'] = $data['referral'];
        }

        $message = Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'whatsapp_message_id' => $data['provider_id'],
            'direction' => 'inbound',
            'type' => $msgData['type'],
            'content' => $content,
            'metadata' => json_encode($meta),
            'status' => 'delivered',
            'media_id' => $mediaId,
            'media_type' => $mediaType,
            'caption' => $caption,
            'attributed_campaign_id' => $attributedCampaignId,
        ]);

        // 6. Fan-out for Side Effects

        // A. Download Media
        if ($mediaId) {
            DownloadMediaJob::dispatch($message->id, $mediaId, $team->id);
        }

        // B. Workflows
        // REMOVED: HandleIncomingWorkflowJob::dispatchSync($message->id, $team->id);
        // Reason: Redundant. MessageReceived event triggers AutomationTriggerListener.

            // Broadcast Real-time Event
            try {
                \App\Events\MessageReceived::dispatch($message);
                Log::info("PersistMessageJob: MessageReceived event dispatched for Message ID: {$message->id}");
            } catch (\Exception $e) {
                Log::error("PersistMessageJob: Failed to dispatch MessageReceived event: " . $e->getMessage());
                // Do not fail the job, as persistence was successful
            }
        } finally {
            $lock->release();
        }
    }

    // --- Helpers Copied from ProcessWebhookJob ---

    protected function extractContent(array $msgData)
    {
        $type = $msgData['type'];
        return match ($type) {
            'text' => $msgData['text']['body'] ?? '',
            'image' => $msgData['image']['caption'] ?? null,
            'video' => $msgData['video']['caption'] ?? null,
            'audio' => null,
            'document' => $msgData['document']['caption'] ?? $msgData['document']['filename'] ?? null,
            'sticker' => null,
            'location' => 'Location',
            'contacts' => '[Contact Card]',
            'interactive' => $this->extractInteractiveContent($msgData['interactive']),
            'button' => $msgData['button']['text'] ?? null,
            default => null,
        };
    }

    protected function extractInteractiveContent($interactive)
    {
        // 1. Check for standard subtypes first (more reliable than 'type' field which can be weird)
        if (isset($interactive['button_reply']['title'])) {
            return $interactive['button_reply']['title'];
        }

        if (isset($interactive['list_reply']['title'])) {
            return $interactive['list_reply']['title'];
        }

        if (isset($interactive['nfm_reply'])) {
            $body = $interactive['nfm_reply']['body'] ?? 'Flow Submitted';
            return "[Flow] " . $body;
        }

        // 2. Fallback to 'type' field logic
        $type = $interactive['type'] ?? 'unknown';

        // Specific handling for Call Permission Grants
        if ($type === 'call_permission_reply' || ($interactive['button_reply']['id'] ?? '') === 'grant_call_permission') {
            return "✅ Call Permission Granted";
        }

        return match ($type) {
            'list_reply' => $interactive['list_reply']['title'] ?? '[List Reply]',
            'button_reply' => $interactive['button_reply']['title'] ?? '[Button Reply]',
            'nfm_reply' => "[Flow] " . ($interactive['nfm_reply']['body'] ?? 'Flow Submitted'),
            default => "[Interactive: $type]",
        };
    }
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("PersistMessageJob FAILED for Message ID: " . ($this->eventPayload['provider_id'] ?? 'unknown'), [
            'error' => $exception->getMessage(),
            'payload' => $this->eventPayload
        ]);
    }
}

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

    /**
     * Create a new job instance.
     * @param array $eventPayload The standardized event payload from the Event Bus.
     */
    public function __construct(array $eventPayload)
    {
        $this->eventPayload = $eventPayload;
        $this->onQueue('messages'); // Persist on the message queue or a separate 'persistence' queue
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->eventPayload;
        Log::info("PersistMessageJob: Starting for Message ID: " . ($data['provider_id'] ?? 'unknown'));

        // extract vital IDs
        $phoneId = $data['to_phone_id'];
        $team = Team::where('whatsapp_phone_number_id', $phoneId)->first();

        if (!$team) {
            Log::error("PersistMessageJob: Team not found for Phone ID: {$phoneId}");
            return;
        }

        // Idempotency: Double check DB
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
        $messageTimestamp = isset($data['timestamp']) ? \Carbon\Carbon::createFromTimestamp($data['timestamp']) : now();

        \App\Models\Contact::where('id', $contact->id)
            ->where(function ($query) use ($messageTimestamp) {
                $query->whereNull('last_interaction_at')
                    ->orWhere('last_interaction_at', '<', $messageTimestamp);
            })
            ->update([
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

        // 6. Create Message Record
        $message = Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'whatsapp_message_id' => $data['provider_id'],
            'direction' => 'inbound',
            'type' => $msgData['type'],
            'content' => $content,
            'metadata' => json_encode($msgData),
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
        HandleIncomingWorkflowJob::dispatch($message->id, $team->id);

        // Note: Real-time broadcast was ALREADY done by the Consumer Command immediately!
        // But if we want to update the "Temp" message or strictly ensure mapped ID is available, we might broadcast again here?
        // Actually, the Consumer Command broadcasts the *Event*. Ideally Frontend uses the Event.
        // But legacy Frontend uses Message model.
        // So we probably SHOULD broadcast MessageReceived here just in case Frontend expects full Model structure.

        \App\Events\MessageReceived::dispatch($message);
    }

    // --- Helpers Copied from ProcessWebhookJob ---

    protected function extractContent(array $msgData)
    {
        $type = $msgData['type'];
        return match ($type) {
            'text' => $msgData['text']['body'] ?? '',
            'image' => $msgData['image']['caption'] ?? '[Image]',
            'video' => $msgData['video']['caption'] ?? '[Video]',
            'audio' => '[Audio]',
            'document' => $msgData['document']['caption'] ?? $msgData['document']['filename'] ?? '[Document]',
            'sticker' => '[Sticker]',
            'location' => 'Location',
            'contacts' => '[Contact Card]',
            'interactive' => $this->extractInteractiveContent($msgData['interactive']), // Fix: method needed
            'button' => $msgData['button']['text'] ?? '[Button]',
            default => "[$type message]",
        };
    }

    protected function extractInteractiveContent($interactive)
    {
        $type = $interactive['type'];
        if ($type === 'list_reply')
            return $interactive['list_reply']['title'] ?? '[List Reply]';
        if ($type === 'button_reply')
            return $interactive['button_reply']['title'] ?? '[Button Reply]';
        return "[Interactive: $type]";
    }
}

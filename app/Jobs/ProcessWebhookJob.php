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
     * Create a new job instance.
     */
    public function __construct($payloadId)
    {
        $this->payloadId = $payloadId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("ProcessWebhookJob started for Payload ID: {$this->payloadId}");
        $payloadRecord = WebhookPayload::find($this->payloadId);

        if (!$payloadRecord) {
            Log::error("Webhook Payload not found: {$this->payloadId}");
            return;
        }

        $payloadRecord->update(['status' => 'processing']);

        try {
            $body = $payloadRecord->payload;

            // Normalize payload to array if it's stored as object/json
            if (is_string($body)) {
                $body = json_decode($body, true);
            }

            if (empty($body['entry'][0]['changes'][0]['value'])) {
                $payloadRecord->update(['status' => 'processed', 'error_message' => 'No changes found']);
                return;
            }

            $change = $body['entry'][0]['changes'][0]['value'];
            $metadata = $change['metadata'] ?? null;

            if (!$metadata) {
                $payloadRecord->update(['status' => 'processed', 'error_message' => 'No metadata']);
                return;
            }

            $phoneId = $metadata['phone_number_id'];

            // Find Team
            $team = Team::where('whatsapp_phone_number_id', $phoneId)->first();

            if (!$team) {
                Log::warning("Webhook received for unknown Phone ID: {$phoneId}");
                $payloadRecord->update(['status' => 'failed', 'error_message' => 'Team not found for Phone ID']);
                // We might want to keep retrying if it's a race condition of team creation? 
                // For now fail.
                return;
            }

            // Add WABA ID to payload record if missing
            if (!$payloadRecord->waba_id) {
                $payloadRecord->update(['waba_id' => $team->whatsapp_business_account_id]);
            }

            // Handle Messages
            if (isset($change['messages'][0])) {
                $msgRequest = $change['messages'][0];
                $contactProfile = $change['contacts'][0] ?? ['wa_id' => $msgRequest['from']];

                $this->processIncomingMessage($team, $msgRequest, $contactProfile);
            }

            // Handle Status Updates
            if (isset($change['statuses'][0])) {
                $this->processStatusUpdate($change['statuses'][0]);
            }

            $payloadRecord->update(['status' => 'processed']);

        } catch (\Exception $e) {
            Log::error("Webhook Processing Failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $payloadRecord->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            // Re-throw to trigger queue retry if configured
            throw $e;
        }
    }

    protected function processIncomingMessage(Team $team, array $msgData, array $contactData)
    {
        Log::info("ProcessWebhookJob: Processing incoming message", [
            'team_id' => $team->id,
            'from' => $msgData['from'],
            'message_id' => $msgData['id']
        ]);
        // Idempotency Check
        if (Message::where('whatsapp_message_id', $msgData['id'])->exists()) {
            Log::info("Message duplicate ignored: {$msgData['id']}");
            return;
        }

        // 1. Find or Create Contact via Service
        $phone = $msgData['from'];
        $name = $contactData['profile']['name'] ?? $phone;

        $contactService = new \App\Services\ContactService();
        $contact = $contactService->createOrUpdate([
            'team_id' => $team->id,
            'phone_number' => $phone,
            'name' => $name,
            // 'custom_attributes' => ['source' => 'whatsapp_webhook'] // Optional: track source
        ]);

        // Update Interaction Time
        $contact->update([
            'last_interaction_at' => now(),
            'last_customer_message_at' => now(), // Fix: Update this to open 24h window
        ]);

        // 1.5 COMPLIANCE: Detect Keywords (Priority)
        $content = $this->extractContent($msgData);
        $cleanContent = strtoupper(trim($content));

        if ($cleanContent === 'STOP') {
            (new \App\Services\ConsentService())->optOut($contact);
            Log::info("Contact {$contact->phone_number} opted out via STOP keyword.");
            // We should still log the message, but maybe skip AI/Bots?
            // Usually, STOP should stop everything immediately.
        } elseif ($cleanContent === 'START') {
            (new \App\Services\ConsentService())->optIn($contact, 'START_KEYWORD');
            Log::info("Contact {$contact->phone_number} opted in via START keyword.");
        }

        // 2. Resolve Conversation
        $conversationService = new \App\Services\ConversationService();
        $conversation = $conversationService->ensureActiveConversation($contact);
        $conversationService->handleIncomingMessage($conversation);

        // 3. Handle Media
        $mediaUrl = null;
        $originalMediaUrl = null;
        $mediaType = null;
        $caption = null;
        $mediaId = null;

        if (in_array($msgData['type'], ['image', 'video', 'audio', 'document', 'sticker'])) {
            $type = $msgData['type'];
            $mediaItem = $msgData[$type];

            $mediaId = $mediaItem['id'] ?? null;
            $caption = $mediaItem['caption'] ?? null;
            $mediaType = $mediaItem['mime_type'] ?? null;

            if ($mediaId) {
                // Dispatch download synchronously or async? 
                // For MVP, sync within job to ensure data consistency.
                try {
                    $mediaService = new \App\Services\MediaService();
                    $mediaUrl = $mediaService->downloadAndStore($mediaId, $team);
                } catch (\Exception $e) {
                    Log::error("Media download failed: " . $e->getMessage());
                }
            }
        }

        // 4. Save Message
        $message = Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'whatsapp_message_id' => $msgData['id'],
            'direction' => 'inbound',
            'type' => $msgData['type'],
            'content' => $this->extractContent($msgData),
            'metadata' => json_encode($msgData),
            'status' => 'delivered',

            // Media Columns
            'media_id' => $mediaId,
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'caption' => $caption,
        ]);

        // 4.5 AI Assistant Check (Shop-by-Chat)
        $commerceConfig = $team->commerce_config ?? [];
        if (($commerceConfig['ai_assistant_enabled'] ?? false) && $msgData['type'] === 'text') {
            try {
                Log::debug("AI Assistant Check: Team={$team->id}, AI_Enabled=" . ($commerceConfig['ai_assistant_enabled'] ? 'YES' : 'NO'));
                $waService = new \App\Services\WhatsAppService(); // Helper
                $aiService = new \App\Services\AiCommerceService($waService);

                $input = $this->extractContent($msgData);
                Log::debug("AI Assistant Input: " . $input);
                $handled = $aiService->handle($contact, $input);

                if ($handled) {
                    Log::info("AI Assistant handled message from {$contact->phone_number}");
                    return; // Stop further processing (Bots/Auto-replies)
                }
            } catch (\Exception $e) {
                Log::error("AI Assistant Logic Failed: " . $e->getMessage());
                // Fallthrough to normal bot/auto-reply
            }
        }

        // 5. Bot Engine Check
        // Only if "Human Inbox" is not specifically overtaking (but typically bots run alongside unless conversation is assigned to human?)
        // Let's assume Bots run unless Stopped.
        $processed = false;
        $triggered = false;

        $input = $this->extractContent($msgData);

        if ($team->ai_auto_reply_enabled) {
            $botService = new \App\Services\AutomationService(new \App\Services\WhatsAppService());

            // 5a. Check for "User Starts Conversation" (First inbound message)
            if ($contact->messages()->where('direction', 'inbound')->count() === 1) {
                $triggered = $botService->checkSpecialTriggers($contact, 'user_starts_conversation');
            }

            // 5b. Try processing active session
            if (!$triggered) {
                $processed = $botService->handleReply($contact, $input);
            }

            // 5c. If not processed, check strictly for Trigger Keywords
            if (!$processed && !$triggered) {
                $triggered = $botService->checkTriggers($contact, trim($input));
            }

            // 5d. Check for Template Response (Quick Reply Buttons)
            if (!$processed && !$triggered && in_array($msgData['type'], ['button', 'interactive'])) {
                $triggered = $botService->checkTemplateTriggers($contact, $input);
            }
        }

        // 6. Business Hours & Welcome Messages
        if (!$processed && !$triggered) {
            $waService = new \App\Services\WhatsAppService();
            $waService->setTeam($team);

            // A. Welcome Message (First Interaction)
            if ($team->welcome_message_enabled && $contact->messages()->where('direction', 'inbound')->count() === 1) {
                try {
                    $this->sendAutoReply($waService, $contact->phone_number, $team->welcome_message, $team->welcome_message_config);
                    return; // Exit after welcome message
                } catch (\Exception $e) {
                    Log::error("Welcome Message Failed: " . $e->getMessage());
                }
            }

            // B. Business Hours / Away Message
            $isWithinHours = $team->isWithinBusinessHours(); // Uses the new array format

            if ($team->away_message_enabled && !$isWithinHours) {
                // Throttle: Don't spam. Check last outbound message time?
                // For MVP: Check if we sent an away message in last 24h?
                $recentOutbound = $conversation->messages()
                    ->where('direction', 'outbound')
                    ->where('created_at', '>', now()->subHours(24))
                    ->exists();

                if (!$recentOutbound) {
                    try {
                        $this->sendAutoReply($waService, $contact->phone_number, $team->away_message, $team->away_message_config);
                    } catch (\Exception $e) {
                        Log::error("Away Message Failed: " . $e->getMessage());
                    }
                }
            }
        }

        // Broadcast
        Log::info("ProcessWebhookJob: Dispatching MessageReceived event", ['message_id' => $message->id]);
        \App\Events\MessageReceived::dispatch($message);

        // 7. Mark as Read (If Enabled)
        if ($team->read_receipts_enabled) {
            try {
                (new \App\Services\WhatsAppService())->setTeam($team)->markRead($msgData['id']);
            } catch (\Exception $e) {
                Log::warning("Failed to mark message as read: " . $e->getMessage());
            }
        }
    }

    protected function processStatusUpdate(array $statusData)
    {
        $waMessageId = $statusData['id'] ?? null;
        $newStatus = $statusData['status'] ?? null;

        if ($waMessageId && $newStatus) {
            $message = Message::where('whatsapp_message_id', $waMessageId)->first();

            if ($message) {
                $updateData = ['status' => $newStatus];
                if ($newStatus === 'delivered') {
                    $updateData['delivered_at'] = now();

                    // Trigger Automation if it's a template
                    if ($message->type === 'template') {
                        $templateName = $message->metadata['template_name'] ?? null;
                        if ($templateName) {
                            $botService = new \App\Services\AutomationService(new \App\Services\WhatsAppService());
                            $botService->checkStatusTriggers($message->contact, $templateName, 'delivered');
                        }
                    }
                }
                if ($newStatus === 'read')
                    $updateData['read_at'] = now();
                if ($newStatus === 'failed')
                    $updateData['error_message'] = $statusData['errors'][0]['message'] ?? 'Unknown error';

                $message->update($updateData);
                \App\Events\MessageStatusUpdated::dispatch($message);
            }
        }
    }

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
            'location' => $this->formatLocation($msgData['location']),
            'contacts' => '[Contact Card]',
            'interactive' => $this->extractInteractiveContent($msgData['interactive']),
            'button' => $msgData['button']['text'] ?? '[Button]',
            default => "[$type message]",
        };
    }

    protected function formatLocation(array $loc)
    {
        return "Location: " . ($loc['name'] ?? 'Unknown') . " (" . ($loc['address'] ?? '') . ")";
    }

    protected function extractInteractiveContent(array $interactive)
    {
        $type = $interactive['type'];

        if ($type === 'list_reply') {
            return $interactive['list_reply']['title'] ?? '[List Reply]';
        }

        if ($type === 'button_reply') {
            return $interactive['button_reply']['title'] ?? '[Button Reply]';
        }

        return "[Interactive: $type]";
    }

    /**
     * Helper to send auto-replies supporting Rich Configuration
     */
    protected function sendAutoReply(\App\Services\WhatsAppService $waService, $to, $legacyText, $config)
    {
        // Fallback to legacy text if no config or config empty
        if (empty($config)) {
            $waService->sendText($to, $legacyText ?? 'Auto-reply');
            return;
        }

        $type = $config['type'] ?? 'regular';

        if ($type === 'regular') {
            $regularType = $config['regular_type'] ?? 'text';
            $content = $config['text'] ?? '';
            $mediaUrl = $config['media_url'] ?? null;
            $caption = $config['caption'] ?? null;

            if ($regularType === 'text') {
                $waService->sendText($to, $content);
            } elseif (in_array($regularType, ['image', 'video', 'audio', 'document'])) {
                if ($mediaUrl) {
                    $waService->sendMedia($to, $regularType, $mediaUrl, $caption);
                } else {
                    Log::warning("Auto-reply media URL missing for type $regularType");
                }
            }
        } elseif ($type === 'template') {
            $name = $config['template_name'] ?? null;
            $lang = $config['language'] ?? 'en_US';

            if ($name) {
                // Assuming no variables for simple auto-replies for now
                $waService->sendTemplate($to, $name, $lang, []);
            }
        }
    }
}

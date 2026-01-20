<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\Team;
use App\Services\AutomationService;
use App\Services\AiCommerceService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HandleIncomingWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $messageId;
    public $teamId;

    public $tries = 2; // AI might fail, don't over-retry if it's a model error
    public $backoff = [30, 60];

    public function __construct($messageId, $teamId)
    {
        $this->messageId = $messageId;
        $this->teamId = $teamId;
    }

    public function handle(): void
    {
        $message = Message::with(['contact', 'team'])->find($this->messageId);
        if (!$message)
            return;

        $team = $message->team;
        $contact = $message->contact;

        $waService = new WhatsAppService();
        $waService->setTeam($team);

        // 1. AI Assistant Check
        $commerceConfig = $team->commerce_config ?? [];
        if (($commerceConfig['ai_assistant_enabled'] ?? false) && $message->type === 'text') {
            try {
                $aiService = new AiCommerceService($waService);
                $handled = $aiService->handle($contact, $message->content);

                if ($handled) {
                    Log::info("AI Assistant handled message {$this->messageId}");
                    return; // Stop further processing
                }
            } catch (\Exception $e) {
                Log::error("AI Assistant Logic Failed in Job: " . $e->getMessage());
            }
        }

        // 2. Automation Service Check
        if ($team->ai_auto_reply_enabled) {
            $botService = new AutomationService($waService);
            $input = $message->content;

            // a. Check for "User Starts Conversation"
            if ($contact->messages()->where('direction', 'inbound')->count() === 1) {
                if ($botService->checkSpecialTriggers($contact, 'user_starts_conversation')) {
                    return;
                }
            }

            // b. Try processing active session
            if ($botService->handleReply($contact, $input)) {
                return;
            }

            // c. Strictly check Trigger Keywords
            if ($botService->checkTriggers($contact, trim($input))) {
                return;
            }

            // d. Template Response
            if (in_array($message->type, ['button', 'interactive'])) {
                if ($botService->checkTemplateTriggers($contact, $input)) {
                    return;
                }
            }
        }

        // 3. Welcome / Away Messages (Business Hours)
        $this->handleAutoReplies($waService, $team, $contact, $message);
    }

    protected function handleAutoReplies($waService, $team, $contact, $message)
    {
        // 1. Welcome Message
        if ($team->welcome_message_enabled && $contact->messages()->where('direction', 'inbound')->count() === 1) {
            $lockKey = "welcome_message_lock:{$contact->id}";
            if (\Illuminate\Support\Facades\Cache::add($lockKey, true, 30)) {
                $this->sendAutoReply($waService, $contact->phone_number, $team->welcome_message, $team->welcome_message_config);
                return;
            }
        }

        // 2. Business Hours / Away Message
        if ($team->away_message_enabled && !$team->isWithinBusinessHours()) {
            $lockKey = "away_message_lock:{$contact->id}";
            if (\Illuminate\Support\Facades\Cache::add($lockKey, true, 3600)) { // 1h lock for away message
                $recentOutbound = $message->conversation->messages()
                    ->where('direction', 'outbound')
                    ->where('created_at', '>', now()->subHours(24))
                    ->exists();

                if (!$recentOutbound) {
                    $this->sendAutoReply($waService, $contact->phone_number, $team->away_message, $team->away_message_config);
                }
            }
        }
    }

    protected function sendAutoReply($waService, $to, $legacyText, $config)
    {
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
                }
            }
        } elseif ($type === 'template') {
            $name = $config['template_name'] ?? null;
            $lang = $config['language'] ?? 'en_US';
            if ($name) {
                $waService->sendTemplate($to, $name, $lang, []);
            }
        }
    }
}

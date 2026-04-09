<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\AutomationService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleIncomingWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $messageId;

    public $teamId;

    public $tries = 2;

    public $backoff = [30, 60];

    public function __construct($messageId, $teamId)
    {
        $this->messageId = $messageId;
        $this->teamId = $teamId;
    }

    public function handle(): void
    {
        // Idempotency check: Ensure we don't process the same message twice
        $lockKey = "processing_msg_{$this->messageId}";
        if (! Cache::add($lockKey, true, 60)) {
            Log::info("Message {$this->messageId} is already being processed. Skipping.");

            return;
        }

        try {
            $message = Message::with(['contact', 'team'])->find($this->messageId);
            if (! $message) {
                return;
            }

            $team = $message->team;
            $contact = $message->contact;

            $waService = new WhatsAppService;
            $waService->setTeam($team);

            // Agent Assignment Check: Bots should stay silent if a human is assigned
            $isAssigned = $contact->assigned_to !== null;

            // 1. Automations (Keywords, Flows, Template Buttons)
            if (! $isAssigned) {
                try {
                    $automationService = app(AutomationService::class);
                    $automationService->setWhatsAppService($waService);

                    // A. Template Button Triggers
                    if ($message->type === 'button') {
                        if ($automationService->checkTemplateTriggers($contact, $message->content)) {
                            Log::info("Automation Triggered: Template Button for message {$this->messageId}");

                            return;
                        }
                    }

                    // B. Flow Completion Triggers
                    if ($message->type === 'interactive') {
                        if ($automationService->checkFlowTriggers($contact, $message)) {
                            Log::info("Automation Triggered: Flow Completion for message {$this->messageId}");

                            return;
                        }
                    }

                    // C. Keyword Triggers
                    if (in_array($message->type, ['text', 'interactive', 'button'])) {
                        if ($automationService->checkTriggers($contact, $message->content)) {
                            Log::info("Automation Triggered: Keyword for message {$this->messageId}");

                            return;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Automation Logic Failed in Job: '.$e->getMessage());
                }
            }

            // 2. AI Assistant Check
            if (! $isAssigned) {
                $commerceConfig = $team->commerce_config ?? [];
                if (($commerceConfig['ai_assistant_enabled'] ?? false) && $message->type === 'text') {
                    // Optimized: Async Dispatch to avoid blocking main queue
                    Log::info("Dispatching AI Job for message {$this->messageId}");
                    ProcessAiAssistantJob::dispatch($this->messageId);

                    return; // Assume AI or Automation handles it.
                }
            }

            // 3. Welcome / Away Messages (Business Hours)
            if (! $isAssigned) {
                $this->handleAutoReplies($waService, $team, $contact, $message);
            }

        } finally {
            // Keep the lock for a bit to prevent race conditions from rapid retries
            // but eventually it will expire.
        }
    }

    protected function handleAutoReplies($waService, $team, $contact, $message)
    {
        // 1. Welcome Message
        if ($team->welcome_message_enabled && $contact->messages()->where('direction', 'inbound')->count() === 1) {
            $lockKey = "welcome_message_lock:{$contact->id}";
            if (Cache::add($lockKey, true, 30)) {
                $this->sendAutoReply($waService, $contact->phone_number, $team->welcome_message, $team->welcome_message_config);

                return;
            }
        }

        // 2. Business Hours / Away Message
        if ($team->away_message_enabled && ! $team->isWithinBusinessHours()) {
            $lockKey = "away_message_lock:{$contact->id}";
            if (Cache::add($lockKey, true, 3600)) {
                $recentOutbound = $message->conversation->messages()
                    ->where('direction', 'outbound')
                    ->where('created_at', '>', now()->subHours(24))
                    ->exists();

                if (! $recentOutbound) {
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

<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Services\AutomationService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AutomationTriggerListener
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     */
    public $queue = 'automations';

    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {
        Log::info("AutomationTriggerListener: Handle started for message {$event->message->id}");
        $message = $event->message;

        // Idempotency check: Ensure we don't process the same message twice
        $idempotencyKey = "automation_triggered_msg_{$message->id}";
        if (Cache::has($idempotencyKey)) {
            Log::info("AutomationTriggerListener: Message {$message->id} already processed. Skipping.");
            return;
        }
        Cache::put($idempotencyKey, true, 3600); // 1 hour window

        // Skip outbound messages
        if ($message->direction !== 'inbound') {
            return;
        }

        $contact = $message->contact;
        $content = $message->content;

        try {
            $automationService = app(AutomationService::class);
            $handoffService = new \App\Services\BotHandoffService();

            Log::info("Automation Listener DEBUG: Starting processing for contact #{$contact->id} with content '{$content}'");

            // 1. Global Handoff Keywords
            $handoffKeywords = ['human', 'agent', 'person', 'representative', 'help', 'support', 'talk to someone'];
            $cleanContent = strtolower(trim($content));
            foreach ($handoffKeywords as $kw) {
                if ($cleanContent === $kw) {
                    Log::info("Automation Listener DEBUG: Global handoff keyword matched: '{$kw}'");
                    $handoffService->pause($contact, 'keyword_trigger');
                    (new \App\Services\AssignmentService())->assign($contact);
                    return;
                }
            }

            // 2. Check active flow (reply handling)
            Log::info("Automation Listener DEBUG: Checking if message is a reply to an active flow...");
            if ($automationService->handleReply($contact, $content, $message)) {
                Log::info("Automation Listener DEBUG: Message handled as a reply for contact #{$contact->id}");
                return;
            }

            // 3. Check Referral Trigger
            $metadata = $message->metadata ?? [];
            if (isset($metadata['referral'])) {
                Log::info("Automation Listener DEBUG: Referral data detected. Checking referral triggers...");
                if ($automationService->checkReferralTriggers($contact, $metadata['referral'])) {
                    Log::info("Automation Listener DEBUG: Referral trigger matched for contact #{$contact->id}");
                    return;
                }
            }

            // 4. Check new keyword triggers
            Log::info("Automation Listener DEBUG: No active flow reply. Checking for new keyword triggers...");
            if ($automationService->checkTriggers($contact, $content)) {
                Log::info("Automation Listener DEBUG: New trigger matched and started for contact #{$contact->id}");
            } else {
                Log::info("Automation Listener DEBUG: [Final] No triggers matched for contact #{$contact->id} (Content: '$content')");
            }

        } catch (\Exception $e) {
            Log::error("Automation Failure for Message {$message->id}: " . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }
}


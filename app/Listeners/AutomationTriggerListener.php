<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Services\AutomationService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AutomationTriggerListener
{
    use InteractsWithQueue;

    public $queue = 'messages';
    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {
        // Ideally we shouldn't consistently forget all instances, but for certain shared services
        // that might hold state from previous jobs in the same daemon process (like WhatsAppService with teamId),
        // we might want a fresh start or ensure we set the team correctly.
        // The AutomationService does setTeam(), but let's be safe.
        // app()->forgetInstances(); // This is too aggressive for queue workers generally.

        Log::info("AutomationTriggerListener: Handle started for message {$event->message->id}");
        $message = $event->message;

        // Idempotency check: Ensure we don't process the same message twice
        $idempotencyKey = "automation_triggered_msg_{$message->id}";
        if (Cache::has($idempotencyKey)) {
            Log::info("AutomationTriggerListener: Message {$message->id} already processed. Skipping.");
            return;
        }
        Cache::put($idempotencyKey, true, 60);

        // Skip outbound messages
        if ($message->direction !== 'inbound') {
            return;
        }

        $contact = $message->contact;
        $content = $message->content; // Or use raw content if needed for matching

        try {
            $automationService = app(AutomationService::class);
            $handoffService = new \App\Services\BotHandoffService();

            // 1. Global Handoff Keywords
            $handoffKeywords = ['human', 'agent', 'person', 'representative', 'help', 'support', 'talk to someone'];
            $cleanContent = strtolower(trim($content));
            foreach ($handoffKeywords as $kw) {
                if ($cleanContent === $kw) {
                    $handoffService->pause($contact, 'keyword_trigger');
                    (new \App\Services\AssignmentService())->assign($contact);
                    return;
                }
            }

            // 2. Check active flow
            if ($automationService->handleReply($contact, $content)) {
                Log::info("AutomationTriggerListener: Handled as reply for contact {$contact->id}");
                return;
            }

            // 3. Check Referral Trigger
            $metadata = $message->metadata ?? [];
            if (isset($metadata['referral'])) {
                if ($automationService->checkReferralTriggers($contact, $metadata['referral'])) {
                    Log::info("AutomationTriggerListener: Referral trigger matched for contact {$contact->id}");
                    return;
                }
            }

            // 4. Check triggers
            if ($automationService->checkTriggers($contact, $content)) {
                Log::info("AutomationTriggerListener: Trigger matched for contact {$contact->id}");
            } else {
                Log::debug("AutomationTriggerListener: No trigger match for contact {$contact->id}");
            }

        } catch (\Exception $e) {
            Log::error("Automation Failure for Message {$message->id}: " . $e->getMessage());
        }
    }
}

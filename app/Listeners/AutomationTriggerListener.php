<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Services\AutomationService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AutomationTriggerListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'messages';
    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {
        $message = $event->message;

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

            // 2. Check triggers
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

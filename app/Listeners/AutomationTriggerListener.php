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
            $automationService = new AutomationService(new WhatsAppService());
            $handoffService = new \App\Services\BotHandoffService();

            // 1. Global Handoff Keywords
            $handoffKeywords = ['human', 'agent', 'person', 'representative', 'help', 'support', 'talk to someone'];
            $cleanContent = strtolower(trim($content));
            foreach ($handoffKeywords as $kw) {
                if ($cleanContent === $kw) {
                    $handoffService->pause($contact, 'keyword_trigger');
                    (new \App\Services\AssignmentService())->assignToBestAgent($contact->team, $contact);
                    return;
                }
            }

            // 2. Check active flow
            if ($automationService->handleReply($contact, $content)) {
                return;
            }

            // 2. Check triggers
            $automationService->checkTriggers($contact, $content);

        } catch (\Exception $e) {
            Log::error("Automation Failure for Message {$message->id}: " . $e->getMessage());
        }
    }
}

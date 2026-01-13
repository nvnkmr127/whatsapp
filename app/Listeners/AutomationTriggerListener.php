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

            // 1. Check active flow
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

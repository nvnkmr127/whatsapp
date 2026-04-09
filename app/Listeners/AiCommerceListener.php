<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Services\AiCommerceService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AiCommerceListener
{
    use InteractsWithQueue;

    protected $aiCommerceService;

    /**
     * Create the event listener.
     */
    public function __construct(AiCommerceService $aiCommerceService)
    {
        $this->aiCommerceService = $aiCommerceService;
    }

    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {
        $message = $event->message;

        // Only process inbound text messages for AI commerce
        if ($message->direction !== 'inbound' || $message->type !== 'text') {
            return;
        }

        $contact = $message->contact;
        $team = $message->team;

        // AI Commerce Service already checks if the assistant is enabled for the team
        try {
            $handled = $this->aiCommerceService->handle($contact, $message->content);

            if ($handled) {
                Log::info("AiCommerceListener: AI Assistant handled message for contact {$contact->id} in team {$team->id}");
            }
        } catch (\Exception $e) {
            Log::error('AiCommerceListener Error: '.$e->getMessage());
        }
    }
}

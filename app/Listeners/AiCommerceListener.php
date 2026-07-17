<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Services\AiCommerceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AiCommerceListener implements ShouldQueue
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

        if ($message->direction !== 'inbound') {
            return;
        }

        // Text arrives as content; voice notes arrive as the Whisper transcription
        // (stored in metadata once DownloadMediaJob finishes). Anything else is ignored.
        $text = match ($message->type) {
            'text' => $message->content,
            'audio' => $message->metadata['transcription'] ?? null,
            default => null,
        };

        if (empty($text)) {
            return;
        }

        $contact = $message->contact;
        $team = $message->team;

        // AI Commerce Service already checks if the assistant is enabled for the team
        try {
            $handled = $this->aiCommerceService->handle($contact, $text);

            if ($handled) {
                Log::info("AiCommerceListener: AI Assistant handled message for contact {$contact->id} in team {$team->id}");
            }
        } catch (\Exception $e) {
            Log::error('AiCommerceListener Error: '.$e->getMessage());
        }
    }
}

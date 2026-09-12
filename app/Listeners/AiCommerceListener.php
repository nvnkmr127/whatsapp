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

        if (! $contact || ! $team) {
            return;
        }

        // 1. Respect Bot Handoff (agent assigned or manually paused)
        if (! app(\App\Services\BotHandoffService::class)->shouldProcess($contact)) {
            return;
        }

        // 2. Priority: If contact is in an active automation run, do not intercept
        if (\App\Models\AutomationRun::where('contact_id', $contact->id)->whereIn('status', ['waiting_input', 'active'])->exists()) {
            Log::debug("AiCommerceListener: Skipping contact {$contact->id} - active automation flow is in progress.");

            return;
        }

        // 3. Priority: If message matches an active keyword automation trigger, let the automation handle it
        $cleanText = mb_strtolower(trim($text));
        $hasTrigger = \App\Models\Automation::where('team_id', $team->id)
            ->where('is_active', true)
            ->whereIn('trigger_type', ['keyword', 'multi'])
            ->get()
            ->contains(function ($automation) use ($cleanText) {
                $keywords = $automation->trigger_config['keywords'] ?? [];
                $isRegex = $automation->trigger_config['is_regex'] ?? false;
                foreach ($keywords as $kw) {
                    $trimmed = trim($kw);
                    if ($trimmed === '') {
                        continue;
                    }
                    if ($isRegex) {
                        $pattern = (! str_starts_with($trimmed, '/') || ! str_ends_with($trimmed, '/')) ? '/'.str_replace('/', '\/', $trimmed).'/i' : $trimmed;
                        if (@preg_match($pattern, $cleanText)) {
                            return true;
                        }
                    } elseif (str_contains($cleanText, mb_strtolower($trimmed))) {
                        return true;
                    }
                }

                return false;
            });

        if ($hasTrigger) {
            Log::debug("AiCommerceListener: Skipping contact {$contact->id} - matches a chatbot automation trigger.");

            return;
        }

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

<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Services\AutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutomationTriggerListener implements ShouldQueue
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
        Log::debug("AutomationTriggerListener: Handle started for message {$event->message->id}");
        $message = $event->message;

        // Idempotency: atomically claim the message so concurrent workers don't double-process.
        // add() is atomic (unlike has()+put()) and returns false if already claimed. The claim is
        // released on failure below so a transient error (e.g. log/DB blip) doesn't poison the
        // message — otherwise a failed attempt permanently skips it for the whole TTL.
        $idempotencyKey = "automation_triggered_msg_{$message->id}";
        if (! Cache::add($idempotencyKey, true, 3600)) {
            Log::debug("AutomationTriggerListener: Message {$message->id} already processed. Skipping.");

            return;
        }

        // Skip outbound messages
        if ($message->direction !== 'inbound') {
            return;
        }

        $contact = $message->contact;
        $content = $message->content;

        try {
            $automationService = app(AutomationService::class);
            $handoffService = app(\App\Services\BotHandoffService::class);
            $assignmentService = app(\App\Services\AssignmentService::class);

            // 1. Global Handoff Keywords
            $handoffKeywords = ['human', 'agent', 'person', 'representative', 'help', 'support', 'talk to someone'];
            $cleanContent = strtolower(trim($content));
            foreach ($handoffKeywords as $kw) {
                if ($cleanContent === $kw) {
                    $handoffService->pause($contact, 'keyword_trigger');
                    $assignmentService->assign($contact);

                    return;
                }
            }

            // 2. Check active flow
            if ($automationService->handleReply($contact, $content, $message)) {
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
                Log::info("AutomationTriggerListener: No trigger match for contact {$contact->id} (Content: '$content')");
            }

        } catch (\Exception $e) {
            // Release the idempotency claim so this message can be retried instead of being
            // silently skipped forever.
            Cache::forget($idempotencyKey);
            Log::error("Automation Failure for Message {$message->id}: ".$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}

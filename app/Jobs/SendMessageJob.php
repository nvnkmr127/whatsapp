<?php

namespace App\Jobs;

use App\Models\Team;
use App\Models\Contact;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $teamId;
    public $phone;
    public $type;
    public $content; // Message body or Array for template
    public $templateName;
    public $language;
    public $messageId;

    /**
     * Create a new job instance.
     */
    public function __construct($teamId, $phone, $type, $content, $templateName = null, $language = 'en_US', $messageId = null)
    {
        $this->onQueue('messages');
        $this->teamId = $teamId;
        $this->phone = $phone;
        $this->type = $type;
        $this->content = $content;
        $this->templateName = $templateName;
        $this->language = $language;
        $this->messageId = $messageId;
    }

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        $team = Team::find($this->teamId);
        if (!$team) {
            Log::error("SendMessageJob: Team not found {$this->teamId}");
            return;
        }

        $waService->setTeam($team);

        $existingMessage = $this->messageId ? \App\Models\Message::find($this->messageId) : null;

        try {
            $response = null;

            if ($this->type === 'text') {
                $response = $waService->sendText($this->phone, $this->content, $existingMessage);
            } elseif ($this->type === 'template') {
                $response = $waService->sendTemplate(
                    $this->phone,
                    $this->templateName,
                    $this->language,
                    $this->content ?? [], // Body Params
                    [], // Header
                    [], // Footer
                    null, // Campaign
                    $existingMessage
                );
            } elseif ($this->type === 'interactive') {
                $buttons = $existingMessage ? ($existingMessage->metadata['buttons'] ?? []) : [];
                $response = $waService->sendInteractiveButtons(
                    $this->phone,
                    $this->content, // text body
                    $buttons,
                    $existingMessage
                );
            } elseif (in_array($this->type, ['image', 'video', 'audio', 'document'])) {
                $response = $waService->sendMedia(
                    $this->phone,
                    $this->type,
                    $this->content, // URL
                    $existingMessage->caption ?? null,
                    $existingMessage
                );
            }

            if (!empty($response['error'])) {
                $errorMsg = json_encode($response['error']);

                // Policy Check: 24h window (code 131047 or similar)
                $errorCode = $response['error']['code'] ?? null;
                if (in_array($errorCode, [131047, 131051])) {
                    Log::warning("SendMessageJob: Permanent policy failure for {$this->phone}. Code: {$errorCode}");
                    // Do not throw, so it doesn't retry
                    return;
                }

                throw new \Exception($errorMsg);
            }

        } catch (\Exception $e) {
            Log::error("Failed to send message to {$this->phone}: " . $e->getMessage());

            // Check for policy strings if code wasn't enough
            if (str_contains($e->getMessage(), 'Policy UC-03') || str_contains($e->getMessage(), '24-hour window')) {
                Log::warning("SendMessageJob: Policy exception detected. Stopping retries.");
                return;
            }

            // Throw to trigger retry for transient issues
            throw $e;
        }
    }
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        if ($this->messageId) {
            $message = \App\Models\Message::find($this->messageId);
            if ($message) {
                // Determine if it was a policy failure or generic
                // We truncate error message to fit DB column if restricted, but usually text is fine
                $errorText = mb_strimwidth($exception->getMessage(), 0, 500, '...');

                $message->update([
                    'status' => 'failed',
                    'error_message' => $errorText
                ]);

                // Broadcast update so UI sees red exclamation immediately
                try {
                    \App\Events\MessageStatusUpdated::dispatch($message);
                } catch (\Exception $e) { /* ignore broadcast fail */
                }
            }
        }

        Log::error("SendMessageJob completely failed for ID: {$this->messageId}. Error: " . $exception->getMessage());
    }
}

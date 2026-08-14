<?php

namespace App\Jobs;

use App\Enums\IntegrationState;
use App\Events\MessageStatusUpdated;
use App\Models\Message;
use App\Models\Team;
use App\Services\TraceContext;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Deliberately NOT `implements ShouldQueue` — sends run inline, synchronously,
 * at all 11 dispatch sites. Every other job in app/Jobs does implement it, and
 * this one still calls $this->onQueue('messages') in its constructor (dead code
 * while it runs inline), so the omission reads like an oversight. It isn't:
 * adding the interface makes outbound WhatsApp messages depend on the
 * whatsapp-queue-messages workers actually running, and stops them dead if
 * they aren't. Don't add it without confirming those workers are live.
 *
 * Tests assert dispatch with Bus::fake(), not Queue::fake(), because a
 * synchronous job never reaches the queue.
 */
class SendMessageJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $teamId;

    public $phone;

    public $type;

    public $content;

    public $templateName;

    public $language;

    public $messageId;

    public $traceId;

    public $headerParams;

    public $footerParams;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct($teamId, $phone = null, $type = null, $content = null, $templateName = null, $language = 'en_US', $messageId = null, $traceId = null, $headerParams = [], $footerParams = [])
    {
        $this->onQueue('messages');

        if ($teamId instanceof Message) {
            $message = $teamId;
            $this->messageId = $message->id;
            $this->teamId = $message->team_id;
            $this->phone = $message->contact->phone_number;
            $this->type = $message->type;

            if ($message->type === 'template') {
                $this->templateName = $message->metadata['template_name'] ?? $message->metadata['template_id'] ?? null;
                $this->content = $message->metadata['variables'] ?? [];
            } elseif (in_array($message->type, ['image', 'video', 'audio', 'document'])) {
                $this->content = $message->full_media_url ?: $message->media_url;
            } else {
                $this->content = $message->content;
            }

            $this->language = $message->metadata['language'] ?? 'en_US';
            $this->headerParams = $message->metadata['header_params'] ?? [];
            $this->footerParams = $message->metadata['footer_params'] ?? [];
        } else {
            $this->teamId = $teamId;
            $this->phone = $phone;
            $this->type = $type;
            $this->content = $content;
            $this->templateName = $templateName;
            $this->language = $language;
            $this->messageId = $messageId;
            $this->traceId = $traceId;
            $this->headerParams = $headerParams;
            $this->footerParams = $footerParams;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        // 1. Restore Trace Context
        if ($this->traceId) {
            TraceContext::set($this->traceId);
        } else {
            TraceContext::ensureTraceId();
        }

        $team = Team::find($this->teamId);
        if (! $team) {
            Log::error("SendMessageJob: Team not found {$this->teamId}");

            return;
        }

        $waService->setTeam($team);

        if ($team->is_sandbox_mode) {
            Log::info("SendMessageJob: Team {$team->id} in SANDBOX mode. Skipping real WhatsApp call for {$this->phone}");
            if ($this->messageId) {
                Message::where('id', $this->messageId)->update([
                    'status' => 'sent',
                    'whatsapp_message_id' => 'sandbox_'.Str::random(10),
                ]);
            }

            return;
        }

        // 1. Idempotency Check
        // If messageId exists, check if it already has a provider ID (meaning it was already sent)
        if ($this->messageId) {
            $existingMessage = Message::find($this->messageId);
            if ($existingMessage && ! empty($existingMessage->whatsapp_message_id)) {
                Log::info("SendMessageJob: Message {$this->messageId} already sent (idempotency triggered). Skipping.");

                return;
            }
        } else {
            $existingMessage = null;
        }

        if ($team->whatsapp_setup_state === IntegrationState::PROVISIONED) {
            if (! empty($team->whatsapp_access_token) && ! empty($team->whatsapp_phone_number_id)) {
                $team->whatsapp_setup_state = IntegrationState::READY;
                if (! $team->isDirty(['whatsapp_access_token', 'whatsapp_phone_number_id'])) {
                    $team->save();
                }
            }
        }

        $state = $team->whatsapp_setup_state;
        $isReady = $state && ($state->isReady() || in_array($state, [IntegrationState::READY_WARNING, IntegrationState::DEGRADED]));

        if (! $isReady) {
            $label = $state ? $state->label() : 'Not Configured';
            $message = "Messaging blocked. Connection state: {$label}.";

            if ($state === IntegrationState::SUSPENDED) {
                $message .= ' This is usually due to a permission issue (#200) or an invalid token. Please go to WhatsApp Settings to reconnect and resolve this.';
            }

            Log::warning("SendMessageJob: Messaging blocked for team {$team->id}. State: {$label}");
            $this->markMessageFailed($message);

            return;
        }

        try {
            $response = null;

            if ($this->type === 'text') {
                $response = $waService->sendText($this->phone, $this->content, $existingMessage);
            } elseif ($this->type === 'template') {
                $response = $waService->sendTemplate(
                    $this->phone,
                    $this->templateName,
                    $this->language,
                    $this->content ?? [],
                    $this->headerParams ?? [],
                    $this->footerParams ?? [],
                    null,
                    $existingMessage
                );
            } elseif ($this->type === 'interactive') {
                $buttons = $existingMessage ? ($existingMessage->metadata['buttons'] ?? []) : [];
                $response = $waService->sendInteractiveButtons(
                    $this->phone,
                    $this->content,
                    $buttons,
                    $existingMessage
                );
            } elseif (in_array($this->type, ['image', 'video', 'audio', 'document'])) {
                if (empty($this->content)) {
                    $this->markMessageFailed("Cannot send media message: Media URL is empty or null. Check file storage.");
                    return;
                }

                Log::info('[SendMessageJob] Dispatching sendMedia', [
                    'message_id' => $this->messageId,
                    'phone' => $this->phone,
                    'type' => $this->type,
                    'content' => $this->content,
                    'has_existing_message' => !empty($existingMessage),
                ]);

                $response = $waService->sendMedia(
                    $this->phone,
                    $this->type,
                    $this->content,
                    $existingMessage ? ($existingMessage->caption ?? null) : null,
                    $existingMessage
                );

                Log::info('[SendMessageJob] sendMedia response', [
                    'message_id' => $this->messageId,
                    'response' => $response,
                ]);
            }

            if (! empty($response['error'])) {
                $errorCode = $response['error']['error']['code'] ?? $response['error']['code'] ?? null;

                // Permanent failures (Policy)
                if (in_array($errorCode, [131047, 131051])) {
                    Log::warning("SendMessageJob: Policy failure for {$this->phone}. Code: {$errorCode}");

                    return;
                }

                // Rate limiting with jittered backoff
                if ($errorCode == 131030) {
                    $backoff = 60 + mt_rand(5, 30);
                    Log::notice("SendMessageJob: Rate Limit hit. Backing off {$backoff}s.");
                    $this->release($backoff);

                    return;
                }

                if ($errorCode == 200) {
                    $this->markMessageFailed("WhatsApp Permission Error (#200): Cannot send messages. If this account was connected via Embedded Signup, the generated 'USER' token cannot be used for messaging per Meta rules. You must either configure a global 'WHATSAPP_SYSTEM_ACCESS_TOKEN' in your .env (if you are a Tech Provider) or manually connect using a System User Token generated from the Facebook Business Settings.");

                    return;
                }

                throw new \Exception(json_encode($response['error']));
            }

        } catch (\Exception $e) {
            Log::error("Failed to send message to {$this->phone}: ".$e->getMessage());

            if (str_contains($e->getMessage(), 'Policy UC-03') || str_contains($e->getMessage(), '24-hour window')) {
                return;
            }

            throw $e;
        }
    }

    protected function markMessageFailed(string $error): void
    {
        if (! $this->messageId) {
            return;
        }

        $message = Message::find($this->messageId);
        if (! $message) {
            return;
        }

        $message->update([
            'status' => 'failed',
            'error_message' => mb_strimwidth($error, 0, 500, '...'),
        ]);

        try {
            MessageStatusUpdated::dispatch($message);
        } catch (\Exception $e) {
            Log::error('Failed to update message status after job failure: '.$e->getMessage(), [
                'message_id' => $this->messageId,
                'trace_id' => $this->traceId,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->markMessageFailed($exception->getMessage());

        Log::error("SendMessageJob completely failed for ID: {$this->messageId}. Error: ".$exception->getMessage());
    }
}

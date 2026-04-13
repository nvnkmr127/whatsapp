<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\Team;
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
            \App\Services\TraceContext::set($this->traceId);
        } else {
            \App\Services\TraceContext::ensureTraceId();
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
                    'whatsapp_message_id' => 'sandbox_'.\Illuminate\Support\Str::random(10),
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
                $response = $waService->sendMedia(
                    $this->phone,
                    $this->type,
                    $this->content,
                    $existingMessage->caption ?? null,
                    $existingMessage
                );
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
                    throw new \Exception("WhatsApp Permission Error (#200): The system does not have permission to send messages for this account. Please reconnect Facebook and ensure the System User has Admin access to the WABA.");
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

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        if ($this->messageId) {
            $message = Message::find($this->messageId);
            if ($message) {
                $message->update([
                    'status' => 'failed',
                    'error_message' => mb_strimwidth($exception->getMessage(), 0, 500, '...'),
                ]);

                try {
                    \App\Events\MessageStatusUpdated::dispatch($message);
                } catch (\Exception $e) {
                    Log::error('Failed to update message status after job failure: '.$e->getMessage(), [
                        'message_id' => $this->messageId,
                        'trace_id' => $this->traceId,
                    ]);
                }
            }
        }

        Log::error("SendMessageJob completely failed for ID: {$this->messageId}. Error: ".$exception->getMessage());
    }
}

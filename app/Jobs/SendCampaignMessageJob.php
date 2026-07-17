<?php

namespace App\Jobs;

use App\Events\CampaignProgressUpdated;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Services\ConversationService;
use App\Services\RateLimitService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendCampaignMessageJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaignId;

    protected $contactId;

    protected $traceId;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 600, 1200, 3600];

    protected $snapshotId;

    public function __construct($campaignId, $contactId, $traceId = null, $snapshotId = null)
    {
        $this->campaignId = $campaignId;
        $this->contactId = $contactId;
        $this->traceId = $traceId ?? \App\Services\TraceContext::getTraceId();
        $this->snapshotId = $snapshotId;
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            // Throttle: 20 messages per second per team to avoid hitting WhatsApp rate limits
            // and to stay within API tier boundaries.
            new \Illuminate\Queue\Middleware\ThrottlesExceptions(10, 5),
        ];
    }

    public function handle(): void
    {
        \App\Services\TraceContext::set($this->traceId);
        \App\Services\TraceContext::ensureTraceId();

        if ($this->batch()?->cancelled()) {
            return;
        }

        $campaign = Campaign::find($this->campaignId);
        $contact = Contact::find($this->contactId);

        if (! $campaign || ! $contact) {
            return;
        }

        // --- PAUSE CHECK ---
        if ($campaign->status === 'paused') {
            $this->release(60); // Check again in 60 seconds

            return;
        }

        // --- CANCELLED / FAILED: drop permanently ---
        if (in_array($campaign->status, ['cancelled', 'failed'], true)) {
            return;
        }

        // --- IDEMPOTENCY CHECK ---
        $lockKey = "campaign_send_lock:{$this->campaignId}:{$this->contactId}";
        if (! Cache::add($lockKey, true, 60)) {
            Log::info("Job already being processed for Campaign {$this->campaignId} to Contact {$this->contactId}");

            return;
        }

        // Check if message already exists for this campaign and contact
        $existingMessage = Message::where('campaign_id', $this->campaignId)
            ->where('contact_id', $this->contactId)
            ->whereIn('status', ['sent', 'delivered', 'read'])
            ->exists();

        if ($existingMessage) {
            Log::info("Message already sent for Campaign {$this->campaignId} to Contact {$this->contactId}");

            return;
        }

        $waService = app(WhatsAppService::class);
        $waService->setTeam($campaign->team);

        $bodyVars = $campaign->template_variables ?? [];
        $headerVars = $campaign->header_params ?? [];

        // Simple personalization
        $bodyVars = array_map(function ($v) use ($contact) {
            return str_replace('{{name}}', $contact->name, $v);
        }, $bodyVars);

        try {
            // --- MID-QUEUE PREFLIGHT GUARANTEE (RE-CHECK LIMITS BEFORE DISPATCHING TO API) ---
            // Recalculates exact constraints mid-flight to stop an "enqueue, then drain" infinite queue exploit.
            $messagesUsed = Message::where('team_id', $campaign->team_id)
                ->where('direction', 'outbound')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            // Re-auth using the current snapshot against wallet and limits
            $preflight = app(\App\Services\OutboundPreflightService::class)->authorize(
                team: $campaign->team,
                feature: 'campaigns',
                limitKey: 'message_limit',
                currentUsage: $messagesUsed,
                cost: 0 // Detailed costing handled atomically by BillingService downstream, but trial/limits checked here.
            );

            if (! $preflight['allowed']) {
                throw new \Exception("Preflight re-check failed mid-queue: {$preflight['reason']} [Code: {$preflight['code']}]");
            }

            // --- RESOLVE TEMPLATE DETAILS (UC-SAFE-09) ---
            $snapshot = $this->snapshotId ? \App\Models\CampaignSnapshot::find($this->snapshotId) : null;
            $templateName = $campaign->template_name ?: ($snapshot?->template_name ?: $campaign->template?->name);
            $templateLanguage = $campaign->template_language ?: ($snapshot?->template_language ?: ($campaign->template?->language ?? 'en_US'));

            if (! $templateName) {
                Log::error("SendCampaignMessageJob: Could not resolve template name for Campaign {$campaign->id}");
                throw new \Exception("Safeguard Block: No template name associated with this campaign.");
            }

            // --- SAFEGUARD: Check Template Status ---
            // Prevent sending if template was paused/rejected mid-campaign
            $tpl = \App\Models\WhatsappTemplate::where('team_id', $campaign->team_id)
                ->where('name', $templateName)
                ->first();

            if ($tpl && $tpl->status !== 'APPROVED') {
                throw new \Exception("Safeguard Block: Template '{$templateName}' is {$tpl->status}.");
            }

            // Find or Create the attempt record
            $message = Message::where('campaign_id', $this->campaignId)
                ->where('contact_id', $this->contactId)
                ->first();

            $response = $waService->sendTemplate(
                $contact->phone_number,
                $templateName,
                $templateLanguage,
                $bodyVars,
                $headerVars,
                [], // Footer
                $campaign->id,
                $message // Pass existing if any
            );

            if (! empty($response['success']) && $response['success']) {
                $campaign->increment('sent_count');

                // --- STORE ATTRIBUTION POINTER ---
                // Store for 48 hours to track temporal replies
                // --- STORE ATTRIBUTION POINTER ---
                // Store for 48 hours to track temporal replies
                Cache::put("last_campaign:contact:{$contact->phone_number}", $this->campaignId, 172800);
            } else {
                $error = $response['error'] ?? 'Unknown API Error';
                throw new \Exception(is_array($error) ? json_encode($error) : $error);
            }

        } catch (\Throwable $e) {
            $source = $campaign; // In this context, source is the campaign
            $retryConfig = $campaign->retry_config ?? [];
            $retryEnabled = $retryConfig['enabled'] ?? false;
            $maxRetries = (int) ($retryConfig['max_retries'] ?? 3);

            Log::error("Campaign {$this->campaignId} Send Failed to Contact {$this->contactId}: ".$e->getMessage());

            // Get or create message record
            $msg = Message::where('campaign_id', $this->campaignId)
                ->where('contact_id', $this->contactId)
                ->first();

            if (! $msg) {
                try {
                    $conversationService = new ConversationService;
                    $conversation = $conversationService->ensureActiveConversation($contact);

                    $msg = Message::create([
                        'team_id' => $campaign->team_id,
                        'contact_id' => $contact->id,
                        'conversation_id' => $conversation->id,
                        'campaign_id' => $this->campaignId,
                        'type' => 'template',
                        'direction' => 'outbound',
                        'status' => 'failed',
                        'content' => "Template: " . ($templateName ?? 'Unknown'),
                    ]);
                } catch (\Exception $createEx) {
                    Log::error('Failed to create error message record: '.$createEx->getMessage());
                }
            }

            $currentRetryCount = (int) ($msg->retry_count ?? 0);

            // Handle Retries
            if ($retryEnabled && $currentRetryCount < $maxRetries && $this->shouldRetry($e)) {
                $newRetryCount = $currentRetryCount + 1;
                $interval = (int) ($retryConfig['retry_interval'] ?? 60);
                $strategy = $retryConfig['retry_strategy'] ?? 'exponential';

                // Calculate delay
                $delaySeconds = ($strategy === 'exponential')
                    ? $interval * pow(2, $currentRetryCount)
                    : $interval;

                $nextRetryAt = now()->addSeconds($delaySeconds);

                if ($msg) {
                    $msg->update([
                        'status' => 'failed',
                        'error_message' => substr($e->getMessage(), 0, 255),
                        'last_error' => $e->getMessage()."\n".$e->getTraceAsString(),
                        'retry_count' => $newRetryCount,
                        'next_retry_at' => $nextRetryAt,
                    ]);
                }

                // Re-dispatch if batch is NOT cancelled
                if (! $this->batch()?->cancelled()) {
                    self::dispatch($this->campaignId, $this->contactId, $this->traceId, $this->snapshotId)
                        ->delay($nextRetryAt);

                    Log::info('Campaign message rescheduled for retry', [
                        'campaign_id' => $this->campaignId,
                        'contact_id' => $this->contactId,
                        'retry_count' => $newRetryCount,
                        'next_retry_at' => $nextRetryAt->toDateTimeString(),
                    ]);
                }
            } else {
                // Final failure
                if ($msg) {
                    $msg->update([
                        'status' => 'failed',
                        'error_message' => substr($e->getMessage(), 0, 255),
                        'last_error' => $e->getMessage()."\n".$e->getTraceAsString(),
                        'next_retry_at' => null,
                    ]);
                }
            }

            // Report failure to RateLimitService for adaptive throttling if it looks like a rate limit error
            if (str_contains(strtolower($e->getMessage()), 'rate limit') || str_contains($e->getMessage(), '429')) {
                (new RateLimitService)->reportCriticalFailure($campaign->team_id, $contact->phone_number);
            }

            // Do NOT throw if we handled it ourselves via rescheduling,
            // unless we want the queue to also see it as an attempt (but we use custom retry_count)
            // If the job is in a batch, failing it might fail the whole batch depending on logic.
            // Our logic in updateCampaignProgress uses counts from the messages table.
        } finally {
            Cache::forget($lockKey);
            $this->updateCampaignProgress();
        }
    }

    protected function updateCampaignProgress(): void
    {
        $processed = Cache::increment("campaign_processed_count:{$this->campaignId}");
        $total = (int) Cache::get("campaign_total_count:{$this->campaignId}");

        if ($total > 0 && $processed >= $total) {
            $campaign = Campaign::find($this->campaignId);
            if ($campaign && in_array($campaign->status, ['processing', 'sending', 'queued'])) {
                $status = $campaign->messages()->where('status', 'failed')->exists()
                    ? 'completed_with_errors'
                    : 'completed';

                $campaign->update([
                    'status' => $status,
                    'completed_at' => now(),
                ]);

                if ($status === 'completed_with_errors') {
                    app(\App\Services\CampaignAlertService::class)->notifyFailure($campaign, 'Campaign completed with '.$campaign->messages()->where('status', 'failed')->count().' errors.');
                }

                Log::info("Campaign {$this->campaignId} marked as {$status}.");

                // Final Progress Broadcast
                event(new CampaignProgressUpdated($campaign));

                // Cleanup
                Cache::forget("campaign_total_count:{$this->campaignId}");
                Cache::forget("campaign_processed_count:{$this->campaignId}");
            }
        } elseif ($processed % 10 === 0) {
            $campaign = Campaign::find($this->campaignId);
            if ($campaign) {
                event(new CampaignProgressUpdated($campaign));
            }
        }
    }

    protected function shouldRetry(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        // Don't retry on common permanent errors
        $permanentErrors = [
            'template not found',
            'blocked by policy',
            'marketing requires opt-in',
            'plan limit reached',
            'insufficient funds',
            'invalid parameter',
            '400 bad request',
        ];

        foreach ($permanentErrors as $error) {
            if (str_contains($message, $error)) {
                return false;
            }
        }

        return true;
    }
}

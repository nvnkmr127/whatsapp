<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Services\WhatsAppService;
use App\Services\ConversationService;
use App\Services\RateLimitService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\CampaignProgressUpdated;

class SendCampaignMessageJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaignId;
    protected $contactId;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 600];

    public function __construct($campaignId, $contactId)
    {
        $this->campaignId = $campaignId;
        $this->contactId = $contactId;
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            // Throttle: 20 messages per second per team to avoid hitting WhatsApp rate limits
            // and to stay within API tier boundaries.
            new \Illuminate\Queue\Middleware\ThrottlesExceptions(10, 5)
        ];
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $campaign = Campaign::find($this->campaignId);
        $contact = Contact::find($this->contactId);

        if (!$campaign || !$contact) {
            return;
        }

        // --- IDEMPOTENCY CHECK ---
        $lockKey = "campaign_send_lock:{$this->campaignId}:{$this->contactId}";
        if (!Cache::add($lockKey, true, 60)) {
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

        $waService = new WhatsAppService();
        $waService->setTeam($campaign->team);

        $bodyVars = $campaign->template_variables ?? [];
        $headerVars = $campaign->header_params ?? [];

        // Simple personalization
        $bodyVars = array_map(function ($v) use ($contact) {
            return str_replace('{{name}}', $contact->name, $v);
        }, $bodyVars);

        try {
            // --- SAFEGUARD: Check Template Status ---
            // Prevent sending if template was paused/rejected mid-campaign
            $tpl = \App\Models\WhatsappTemplate::where('team_id', $campaign->team_id)
                ->where('name', $campaign->template_name)
                ->first();

            if ($tpl && $tpl->status !== 'APPROVED') {
                throw new \Exception("Safeguard Block: Template '{$campaign->template_name}' is {$tpl->status}.");
            }

            // Find or Create the attempt record
            $message = Message::where('campaign_id', $this->campaignId)
                ->where('contact_id', $this->contactId)
                ->first();

            $response = $waService->sendTemplate(
                $contact->phone_number,
                $campaign->template_name,
                $campaign->template_language,
                $bodyVars,
                $headerVars,
                [], // Footer
                $campaign->id,
                $message // Pass existing if any
            );

            if (!empty($response['success']) && $response['success']) {
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
            Log::error("Campaign {$this->campaignId} Send Failed to Contact {$this->contactId}: " . $e->getMessage());

            // Mark as failed in DB if message exists
            $msg = Message::where('campaign_id', $this->campaignId)
                ->where('contact_id', $this->contactId)
                ->where('status', 'queued') // Only update if still queued
                ->first();

            if ($msg) {
                $msg->update([
                    'status' => 'failed',
                    'error_message' => substr($e->getMessage(), 0, 255),
                    'metadata' => array_merge($msg->metadata ?? [], ['last_error' => $e->getMessage()])
                ]);
            }

            // Decide if we should retry
            if ($this->shouldRetry($e)) {
                // Report failure to RateLimitService for adaptive throttling if it looks like a rate limit error
                if (str_contains(strtolower($e->getMessage()), 'rate limit') || str_contains($e->getMessage(), '429')) {
                    (new RateLimitService())->reportCriticalFailure($campaign->team_id, $contact->phone_number);
                }
                throw $e;
            }
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
            '400 bad request'
        ];

        foreach ($permanentErrors as $error) {
            if (str_contains($message, $error)) {
                return false;
            }
        }

        return true;
    }
}

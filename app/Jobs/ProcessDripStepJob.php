<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\Contact;
use App\Models\Message;
use App\Services\WhatsAppService;
use App\Services\ConversationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessDripStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaignId;
    protected $contactId;
    protected $stepIndex;

    public $tries = 3;
    public $backoff = [60, 300, 600];

    public function __construct($campaignId, $contactId, $stepIndex = 0)
    {
        $this->campaignId = $campaignId;
        $this->contactId = $contactId;
        $this->stepIndex = $stepIndex;
        $this->onQueue('campaigns');
    }

    public function handle(): void
    {
        $campaign = Campaign::find($this->campaignId);
        $contact = Contact::find($this->contactId);
        $detail = CampaignDetail::where('campaign_id', $this->campaignId)
            ->where('contact_id', $this->contactId)
            ->first();

        if (!$campaign || !$contact || !$detail) {
            return;
        }

        if ($campaign->status === 'paused') {
            $this->release(60);
            return;
        }

        $steps = $campaign->steps ?? [];
        if (!isset($steps[$this->stepIndex])) {
            Log::info("ProcessDripStepJob: Step index {$this->stepIndex} not found for Campaign {$this->campaignId}");
            return;
        }

        $step = $steps[$this->stepIndex];
        $templateName = $step['template_name'] ?? null;
        if (!$templateName) return;

        $waService = new WhatsAppService();
        $waService->setTeam($campaign->team);

        $bodyVars = $step['variables'] ?? [];
        $headerVars = $step['header_params'] ?? [];

        // Personalization
        $bodyVars = array_map(function ($v) use ($contact) {
            return str_replace('{{name}}', $contact->name, $v);
        }, $bodyVars);

        try {
            $response = $waService->sendTemplate(
                $contact->phone_number,
                $templateName,
                $step['language'] ?? $campaign->template_language ?? 'en_US',
                $bodyVars,
                $headerVars,
                [],
                $campaign->id
            );

            if (!empty($response['success']) && $response['success']) {
                $detail->update([
                    'current_step' => $this->stepIndex + 1,
                    'status' => 'sent'
                ]);

                // Schedule next step if exists
                $nextIndex = $this->stepIndex + 1;
                if (isset($steps[$nextIndex])) {
                    $nextStep = $steps[$nextIndex];
                    $delay = (int) ($nextStep['delay_minutes'] ?? 0);
                    $nextRun = now()->addMinutes($delay);

                    $detail->update(['next_step_at' => $nextRun]);

                    self::dispatch($this->campaignId, $this->contactId, $nextIndex)
                        ->delay($nextRun);
                } else {
                    $detail->update(['status' => 'completed']);
                }

                $campaign->increment('sent_count');
            } else {
                throw new \Exception($response['error'] ?? 'Unknown API Error');
            }

        } catch (\Throwable $e) {
            Log::error("Drip Campaign {$this->campaignId} Step {$this->stepIndex} Failed for Contact {$this->contactId}: " . $e->getMessage());
            $detail->update(['status' => 'failed']);
            throw $e;
        }
    }
}

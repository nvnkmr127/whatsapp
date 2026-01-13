<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Services\WhatsAppMessageService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessCampaignJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout;

    public function __construct(
        protected Campaign $campaign,
        protected int $chunkSize = 100
    ) {
        $this->onQueue(json_decode(get_setting('whatsapp.queue'), true)['name']);
        $this->timeout = json_decode(get_setting('whatsapp.queue'), true)['timeout'] ?? 60;
    }

    public function handle(WhatsAppMessageService $whatsapp): void
    {
        if ($this->campaign->pause_campaign) {
            $this->release(json_decode(get_setting('whatsapp.queue'), true)['retry_after'] ?? 180);

            return;
        }

        try {
            CampaignDetail::query()
                ->where('campaign_id', $this->campaign->id)
                ->where('status', '!=', 2)
                ->where(function ($query) {
                    $query->whereNull('message_status')
                        ->orWhereIn('message_status', ['failed', 'pending']);
                })
                ->chunkById($this->chunkSize, function ($details) {
                    foreach ($details as $detail) {
                        SendCampaignMessageJob::dispatch($detail)
                            ->onQueue(json_decode(get_setting('whatsapp.queue'), true)['name']);
                    }
                });

            $this->campaign->update([
                'is_sent'           => true,
                'last_processed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    protected function handleFailure(Throwable $e): void
    {
        $this->campaign->update([
            'last_error'     => $e->getMessage(),
            'pause_campaign' => true,
        ]);

        if (json_decode(get_setting('whatsapp.logging'), true)['detailed']) {
            logger()->channel(json_decode(get_setting('whatsapp.logging'), true)['channel'])
                ->error('Campaign processing failed', [
                    'campaign_id' => $this->campaign->id,
                    'error'       => $e->getMessage(),
                ]);
        }
    }
}

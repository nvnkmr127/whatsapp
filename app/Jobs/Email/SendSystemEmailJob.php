<?php

namespace App\Jobs\Email;

use App\Enums\EmailUseCase;
use App\Services\Email\EmailDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class SendSystemEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     * We use a high number because EmailDispatcher handles internal SMTP failover.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * Exponential backoff: 30s, 60s, 120s
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function __construct(
        protected string $to,
        protected EmailUseCase $useCase,
        protected Mailable $mailable,
        protected ?int $templateId = null
    ) {
    }

    public function handle(EmailDispatcher $dispatcher): void
    {
        try {
            $dispatcher->send($this->to, $this->useCase, $this->mailable, $this->templateId);
        } catch (\Exception $e) {
            Log::error("SendSystemEmailJob Attempt Failed", [
                'to' => $this->to,
                'use_case' => $this->useCase,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage()
            ]);

            // If we have more tries, we let the queue handle it.
            // If it's the last attempt, we might trigger a secondary fallback (e.g. SMS) if it's an OTP.
            if ($this->attempts() >= $this->tries) {
                $this->handlePermanentFailure();
            }

            throw $e;
        }
    }

    protected function handlePermanentFailure(): void
    {
        Log::critical("System Email Delivery Permanently Failed", [
            'to' => $this->to,
            'use_case' => $this->useCase->value
        ]);

        // Logic for cross-channel fallback (e.g. Send SMS if Email OTP fails) would go here.
    }
}

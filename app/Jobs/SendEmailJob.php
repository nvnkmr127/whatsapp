<?php

namespace App\Jobs;

use App\Services\Email\Contracts\EmailPayload;
use App\Services\Email\EmailProviderManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 60, 300];

    public function __construct(
        public EmailPayload $payload
    ) {
        $this->onQueue('notifications');
    }

    public function handle(EmailProviderManager $manager, \App\Services\Email\EmailDispatcher $dispatcher): void
    {
        $result = $manager->send($this->payload);

        // Final result logging
        $dispatcher->logResult(
            $this->payload->to,
            $this->payload->useCase,
            $result,
            $this->payload->subject,
            $this->payload->metadata['template_id'] ?? null
        );

        if (!$result->success) {
            throw new \Exception("Email delivery failure ({$result->providerName}): " . $result->error);
        }
    }
}

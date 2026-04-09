<?php

namespace App\Services\Email\Drivers;

use App\Enums\EmailUseCase;
use App\Services\Email\Contracts\EmailPayload;
use App\Services\Email\Contracts\EmailProviderContract;
use App\Services\Email\Contracts\EmailResult;
use Illuminate\Support\Facades\Cache;
use Resend;

class ResendDriver implements EmailProviderContract
{
    private $client;

    public function __construct(
        protected string $apiKey,
        protected string $fromAddress,
        protected string $fromName
    ) {
        $this->client = Resend::client($this->apiKey);
    }

    public function send(EmailPayload $payload): EmailResult
    {
        try {
            $data = [
                'from' => "{$this->fromName} <{$this->fromAddress}>",
                'to' => [$payload->to],
                'subject' => $payload->subject,
                'html' => $payload->html,
            ];

            if ($payload->text) {
                $data['text'] = $payload->text;
            }

            if ($payload->replyTo) {
                $data['reply_to'] = $payload->replyTo;
            }

            // Task 3.5: Marketing headers for Gmail's one-click unsubscribe
            if ($payload->useCase === EmailUseCase::MARKETING && isset($payload->headers['List-Unsubscribe'])) {
                $data['headers'] = [
                    'List-Unsubscribe' => $payload->headers['List-Unsubscribe'],
                ];
            }

            $response = $this->client->emails->send($data);

            return EmailResult::ok('resend', $response->id);

        } catch (\Exception $e) {
            return EmailResult::fail('resend', $e->getMessage());
        }
    }

    public function supports(EmailUseCase $useCase): bool
    {
        // Support MARKETING, ALERT and NOTIFICATION
        // WhatsApp handles OTP
        return in_array($useCase, [
            EmailUseCase::MARKETING,
            EmailUseCase::ALERT,
            EmailUseCase::NOTIFICATION,
        ]);
    }

    public function getName(): string
    {
        return 'resend';
    }

    public function isHealthy(): bool
    {
        return Cache::remember('resend_health', 300, function () {
            try {
                // Listing domains or API keys as a lightweight check
                $this->client->emails->list(['limit' => 1]);

                return true;
            } catch (\Exception $e) {
                return false;
            }
        });
    }
}

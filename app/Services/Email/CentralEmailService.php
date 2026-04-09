<?php

namespace App\Services\Email;

use App\Enums\EmailUseCase;
use App\Mail\DynamicSystemMail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class CentralEmailService
{
    protected $dispatcher;

    protected $templateService;

    public function __construct(EmailDispatcher $dispatcher, EmailTemplateService $templateService)
    {
        $this->dispatcher = $dispatcher;
        $this->templateService = $templateService;
    }

    /**
     * Send an OTP with rate limiting and enforcement.
     */
    public function sendOtp(string $to, array $data): void
    {
        // 1. Rate Limiting per Recipient (Security)
        $this->enforceRateLimit($to, 'otp_delivery', 3, 60); // Max 3 per minute

        // 2. Delivery
        $this->sendTemplatedEmail($to, 'user-otp-login', $data, EmailUseCase::OTP);
    }

    /**
     * Send a system email with raw content.
     */
    public function sendSystemEmail(string $to, string $subject, string $html, string $text, EmailUseCase $useCase = EmailUseCase::ALERT, array $headers = []): void
    {
        try {
            $mailable = new DynamicSystemMail($subject, $html, $text, $headers);

            // Dispatch via failover engine using the specified use case
            \App\Jobs\Email\SendSystemEmailJob::dispatch($to, $useCase, $mailable, null);

        } catch (\Exception $e) {
            Log::error('CentralEmailService: Failed to queue raw system email', [
                'to' => $to,
                'subject' => $subject,
                'useCase' => $useCase->value,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Centralized method to send any templated system email.
     */
    public function sendTemplatedEmail(string $to, string $slug, array $data, EmailUseCase $useCase): void
    {
        try {
            // 1. Render Template (Enforces strict schema variables)
            $rendered = $this->templateService->render($slug, $data);
            $template = EmailTemplate::where('slug', $slug)->first();

            // 2. Handle specific headers (e.g. List-Unsubscribe for Marketing)
            $headers = [];
            if ($useCase === EmailUseCase::MARKETING && isset($data['unsubscribe_url'])) {
                $headers['List-Unsubscribe'] = "<{$data['unsubscribe_url']}>";
            }

            // 3. Dispatch
            $this->sendSystemEmail($to, $rendered['subject'], $rendered['html'], $rendered['text'], $useCase, $headers);

        } catch (\Exception $e) {
            Log::error('CentralEmailService: Failed to queue templated email', [
                'to' => $to,
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Strict Rate Limiting to prevent SMS/Email bombing and cost spikes.
     */
    protected function enforceRateLimit(string $key, string $type, int $maxAttempts, int $decaySeconds): void
    {
        $limitKey = "email_limit:{$type}:".sha1($key);

        if (RateLimiter::tooManyAttempts($limitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($limitKey);
            Log::warning("Rate limit exceeded for {$type}", ['key' => $key, 'wait' => $seconds]);
            throw new \Exception("Too many requests. Please try again in {$seconds} seconds.");
        }

        RateLimiter::hit($limitKey, $decaySeconds);
    }
}

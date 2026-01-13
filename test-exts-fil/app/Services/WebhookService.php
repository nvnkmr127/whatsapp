<?php

namespace App\Services;

use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;

class WebhookService
{
    protected function generateSignature(array $payload, string $secret): string
    {
        $payloadString = json_encode($payload);

        return hash_hmac('sha256', $payloadString, $secret);
    }

    protected function getHeaders(array $payload, string $secret): array
    {
        $defaultHeaders = config('webhooks.headers', []);

        return array_merge($defaultHeaders, [
            'X-Webhook-Signature' => $this->generateSignature($payload, $secret),
            'X-Webhook-Event'     => $payload['event'],
            'X-Webhook-Timestamp' => now()->toIso8601String(),
        ]);
    }

    public function send(string $url, array $payload, string $secret): bool
    {
        $maxAttempts = config('webhooks.retry.max_attempts', 3);
        $timeout     = config('webhooks.retry.timeout', 30);
        $attempt     = 1;
        $success     = false;

        do {
            try {
                $headers  = $this->getHeaders($payload, $secret);
                $response = Http::timeout($timeout)
                    ->withHeaders($headers)
                    ->post($url, $payload);

                $this->logWebhook([
                    'event'         => $payload['event'],
                    'model'         => $payload['model'],
                    'url'           => $url,
                    'status'        => $response->successful() ? 'success' : 'failed',
                    'attempt'       => $attempt,
                    'payload'       => $payload,
                    'response'      => $response->json(),
                    'status_code'   => $response->status(),
                    'error_message' => $response->failed() ? $response->body() : null,
                ]);

                if ($response->successful()) {
                    $success = true;
                    break;
                }

                whatsapp_log('Webhook failed', 'error', [
                    'url'      => $url,
                    'attempt'  => $attempt,
                    'status'   => $response->status(),
                    'response' => $response->body(),
                ]);

            } catch (\Throwable $e) {
                $this->logWebhook([
                    'event'         => $payload['event'],
                    'model'         => $payload['model'],
                    'url'           => $url,
                    'status'        => 'error',
                    'attempt'       => $attempt,
                    'payload'       => $payload,
                    'error_message' => $e->getMessage(),
                ]);

                whatsapp_log('Webhook error', 'error', [
                    'url'     => $url,
                    'attempt' => $attempt,
                ], $e);
            }

            if ($attempt < $maxAttempts) {
                sleep(pow(2, $attempt - 1));
            }

            $attempt++;
        } while ($attempt <= $maxAttempts);

        return $success;
    }

    protected function logWebhook(array $data): void
    {
        if (! config('webhooks.tracking.enabled', true)) {
            return;
        }

        try {
            WebhookLog::create($data);

            $daysToKeep = config('webhooks.tracking.cleanup_after_days', 30);
            if ($daysToKeep > 0) {
                WebhookLog::cleanup($daysToKeep);
            }
        } catch (\Throwable $e) {
            whatsapp_log('Failed to log webhook', 'error', [
                'data' => $data,
            ], $e);
        }
    }

    public function validateSignature(string $signature, array $payload, string $secret): bool
    {
        $expectedSignature = $this->generateSignature($payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}

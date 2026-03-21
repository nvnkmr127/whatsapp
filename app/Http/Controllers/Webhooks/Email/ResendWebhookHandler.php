<?php

namespace App\Http\Controllers\Webhooks\Email;

use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Resend\WebhookSignature;

class ResendWebhookHandler
{
    /**
     * Handle Resend webhook events.
     */
    public function handle(Request $request): void
    {
        $payload = $request->getContent();
        $webhookSecret = config('services.resend.webhook_secret');

        // 1. Verify Signature
        if ($webhookSecret) {
            try {
                // Formatting headers for WebhookSignature::verify
                $headers = [
                    'svix-id' => $request->header('svix-id'),
                    'svix-timestamp' => $request->header('svix-timestamp'),
                    'svix-signature' => $request->header('svix-signature'),
                ];

                WebhookSignature::verify($payload, $headers, $webhookSecret);
            } catch (\Exception $e) {
                Log::warning("Resend Webhook signature verification failed: " . $e->getMessage());
                abort(403, 'Invalid signature');
            }
        }

        // 2. Process Event
        $type = $request->json('type');
        $data = $request->json('data');
        $emailId = $data['email_id'] ?? null;

        if (!$emailId) {
            Log::info("Resend Webhook: Missing email_id in payload type [{$type}]");
            return;
        }

        $log = EmailLog::where('message_id', $emailId)->first();
        if (!$log) {
            Log::info("Resend Webhook: No EmailLog found for message_id [{$emailId}]");
            return;
        }

        switch ($type) {
            case 'email.delivered':
                $log->update([
                    'delivered_at' => now(),
                    'status' => 'delivered'
                ]);
                break;

            case 'email.bounced':
                $log->update([
                    'failed_at' => now(),
                    'status' => 'failed',
                    'failure_reason' => $data['bounce']['message'] ?? 'Permanent Bounce',
                    'failure_type' => 'bounce',
                ]);
                break;

            case 'email.complained':
                $log->update([
                    'status' => 'complained',
                    'metadata' => array_merge($log->metadata ?? [], [
                        'complaint' => $data['complaint'] ?? []
                    ])
                ]);
                break;
                
            default:
                Log::info("Resend Webhook: Unhandled event type [{$type}] for message_id [{$emailId}]");
                break;
        }
    }
}

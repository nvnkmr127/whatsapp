<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verify the webhook (GET).
     */
    public function verify(Request $request)
    {
        // Read from Settings table (global for all teams)
        $verifyToken = get_setting('whatsapp_webhook_verify_token');

        // Fallback to config if not set in database
        if (empty($verifyToken)) {
            $verifyToken = config('whatsapp.webhook_verify_token');
        }

        if (empty($verifyToken)) {
            Log::error('WhatsApp webhook verify token not configured');

            return response('Webhook verify token not configured', 500);
        }

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming events (POST).
     */
    public function handle(Request $request)
    {
        $startTime = microtime(true);
        $data = $request->all();
        $signature = $request->header('X-Hub-Signature-256');

        // 1. Signature Verification (Security First)
        if (!$this->verifySignature($request)) {
            Log::warning('WhatsApp Webhook: Invalid Signature', ['signature' => $signature]);
            return response('Invalid Signature', 403);
        }

        // Store Raw Payload
        try {
            $payloadRecord = \App\Models\WebhookPayload::create([
                'payload' => $data,
                'signature' => $signature,
                'status' => 'pending',
                'waba_id' => $data['entry'][0]['id'] ?? null,
            ]);

            // Update Health Pulse for the team
            $wabaId = $data['entry'][0]['id'] ?? null;
            if ($wabaId) {
                Team::where('whatsapp_business_account_id', $wabaId)
                    ->update(['last_webhook_received_at' => now()]);
            }

            // Dispatch Job with Trace Context (Async for performance)
            $traceId = \App\Services\TraceContext::getTraceId();
            \App\Jobs\ProcessWebhookJob::dispatch($payloadRecord->id, $traceId)
                ->onQueue('webhooks');

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::info('WhatsApp Webhook Handled', [
                'payload_id' => $payloadRecord->id,
                'trace_id' => $traceId,
                'duration_ms' => $duration,
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('WhatsApp Webhook: Failed to store or dispatch', [
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            return response('Internal Error', 500);
        }

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Verify the X-Hub-Signature-256 header.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (! $signature) {
            return false;
        }

        $secret = config('whatsapp.app_secret');
        if (! $secret) {
            Log::critical('WhatsApp Webhook: APP_SECRET not configured. Blocking all webhooks for safety.');

            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        // signature is in format 'sha256=HEX_HASH'
        $actual = str_replace('sha256=', '', $signature);

        return hash_equals($expected, $actual);
    }

    /**
     * Mask sensitive fields in a payload.
     */
    protected function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['phone', 'display_phone_number', 'wa_id', 'from', 'body', 'text', 'caption', 'name'];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys) && is_string($value)) {
                if (in_array($key, ['phone', 'wa_id', 'from'])) {
                    $value = substr($value, 0, 4).'****'.substr($value, -2);
                } else {
                    $value = '********';
                }
            }
        });

        return $data;
    }
}

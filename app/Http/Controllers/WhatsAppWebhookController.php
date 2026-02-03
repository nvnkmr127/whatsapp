<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Contact;
use App\Models\Message;
use App\Services\WhatsAppService;
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
            $verifyToken = config('services.whatsapp.verify_token');
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
        if (config('app.debug')) {
            $logMsg = date('Y-m-d H:i:s') . " RAW WEBHOOK RECEIVED: " . json_encode($request->all()) . "\n";
            \Illuminate\Support\Facades\File::append(base_path('app_debug.log'), $logMsg);
        }

        Log::info("WhatsAppWebhookController: Webhook Received Raw", ['payload' => json_encode($request->all())]);

        $data = $request->all();
        $signature = $request->header('X-Hub-Signature-256');

        // Store Raw Payload
        try {
            Log::debug("WhatsApp Webhook: Attempting to store payload", ['waba_id' => $data['entry'][0]['id'] ?? 'N/A']);
            $payloadRecord = \App\Models\WebhookPayload::create([
                'payload' => $data,
                'signature' => $signature,
                'status' => 'pending',
                'waba_id' => $data['entry'][0]['id'] ?? null,
            ]);

            Log::debug("WhatsApp Webhook: Payload stored", ['payload_id' => $payloadRecord->id]);

            // Dispatch Job with Trace Context
            $traceId = \App\Services\TraceContext::getTraceId();
            \App\Jobs\ProcessWebhookJob::dispatch($payloadRecord->id, $traceId);

            Log::debug("WhatsApp Webhook: ProcessWebhookJob dispatched", ['payload_id' => $payloadRecord->id, 'trace_id' => $traceId]);

        } catch (\Exception $e) {
            Log::error("WhatsApp Webhook: Failed to store or dispatch: " . $e->getMessage());
            return response('Internal Error', 500);
        }

        return response('EVENT_RECEIVED', 200);
    }

}

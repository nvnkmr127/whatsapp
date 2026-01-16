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
        $verifyToken = config('services.whatsapp.verify_token', 'my-secret-token');

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
        $data = $request->all();
        $signature = $request->header('X-Hub-Signature-256');

        // Store Raw Payload
        try {
            $payloadRecord = \App\Models\WebhookPayload::create([
                'payload' => $data,
                'signature' => $signature,
                'status' => 'pending',
            ]);

            // Dispatch Job
            \App\Jobs\ProcessWebhookJob::dispatch($payloadRecord->id);

        } catch (\Exception $e) {
            Log::error("Failed to store webhook: " . $e->getMessage());
            return response('Internal Error', 500);
        }

        return response('EVENT_RECEIVED', 200);
    }

}

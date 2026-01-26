<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\WhatsAppCall;
use App\Models\Contact;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppCallWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp call webhooks.
     */
    public function handle(Request $request)
    {
        // Verify webhook signature (important for security)
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning("Invalid webhook signature for call webhook");
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();

        Log::info("Received WhatsApp call webhook", ['payload' => $payload]);

        try {
            // WhatsApp sends webhook in this format
            $entry = $payload['entry'][0] ?? null;
            if (!$entry) {
                return response()->json(['status' => 'no entry'], 200);
            }

            $changes = $entry['changes'][0] ?? null;
            if (!$changes) {
                return response()->json(['status' => 'no changes'], 200);
            }

            $value = $changes['value'] ?? null;
            if (!$value) {
                return response()->json(['status' => 'no value'], 200);
            }

            // Extract call information
            $callData = $value['call'] ?? null;
            if (!$callData) {
                return response()->json(['status' => 'no call data'], 200);
            }

            // Get phone number ID to identify the team
            $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
            if (!$phoneNumberId) {
                Log::error("No phone_number_id in call webhook");
                return response()->json(['error' => 'Missing phone_number_id'], 400);
            }

            // Find team by phone number ID
            $team = Team::where('whatsapp_phone_number_id', $phoneNumberId)->first();
            if (!$team) {
                Log::error("Team not found for phone_number_id: {$phoneNumberId}");
                return response()->json(['error' => 'Team not found'], 404);
            }

            // Process the call event
            $this->processCallEvent($team, $callData);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error("Error processing call webhook", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent WhatsApp from retrying
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 200);
        }
    }

    /**
     * Process call event based on type.
     */
    protected function processCallEvent(Team $team, array $callData)
    {
        $callId = $callData['id'] ?? null;
        $from = $callData['from'] ?? null;
        $to = $callData['to'] ?? null;
        $status = $callData['status'] ?? null;
        $timestamp = $callData['timestamp'] ?? null;

        if (!$callId) {
            Log::warning("Call webhook missing call ID");
            return;
        }

        // Determine if this is an inbound or outbound call
        $direction = ($from === $team->whatsapp_phone_number_id) ? 'outbound' : 'inbound';

        // Find or create call record
        $call = WhatsAppCall::firstOrCreate(
            [
                'call_id' => $callId,
                'team_id' => $team->id,
            ],
            [
                'direction' => $direction,
                'status' => 'initiated',
                'from_number' => $from,
                'to_number' => $to,
                'initiated_at' => $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : now(),
            ]
        );

        // Handle different call statuses
        switch ($status) {
            case 'ringing':
                $this->handleRinging($call);
                break;
            case 'in_progress':
            case 'answered':
                $this->handleAnswered($call);
                break;
            case 'completed':
                $this->handleCompleted($call, $callData);
                break;
            case 'failed':
                $this->handleFailed($call, $callData);
                break;
            case 'rejected':
                $this->handleRejected($call);
                break;
            case 'missed':
            case 'no_answer':
                $this->handleMissed($call);
                break;
            default:
                Log::info("Unknown call status: {$status}", ['call_id' => $callId]);
        }

        // Create or update contact and conversation
        if ($direction === 'inbound') {
            $this->ensureContactAndConversation($team, $call, $from);
        }
    }

    /**
     * Handle ringing status.
     */
    protected function handleRinging(WhatsAppCall $call)
    {
        if ($call->status !== 'ringing') {
            $call->update(['status' => 'ringing']);

            Log::info("Call ringing", [
                'call_id' => $call->call_id,
                'direction' => $call->direction,
            ]);

            // Dispatch event for real-time notifications
            event(new \App\Events\CallRinging($call));
        }
    }

    /**
     * Handle answered status.
     */
    protected function handleAnswered(WhatsAppCall $call)
    {
        if (!in_array($call->status, ['in_progress', 'answered'])) {
            $call->markAsAnswered();

            Log::info("Call answered", [
                'call_id' => $call->call_id,
                'answered_at' => $call->answered_at,
            ]);

            // Dispatch event
            event(new \App\Events\CallAnswered($call));
        }
    }

    /**
     * Handle completed status.
     */
    protected function handleCompleted(WhatsAppCall $call, array $callData)
    {
        if ($call->status !== 'completed') {
            // Extract duration if provided
            $duration = $callData['duration'] ?? null;

            if ($duration) {
                $call->update([
                    'status' => 'completed',
                    'ended_at' => now(),
                    'duration_seconds' => $duration,
                    'cost_amount' => $call->calculateCost($duration),
                ]);
            } else {
                $call->markAsEnded();
            }

            Log::info("Call completed", [
                'call_id' => $call->call_id,
                'duration' => $call->duration_seconds,
                'cost' => $call->cost_amount,
            ]);

            // Dispatch event
            event(new \App\Events\CallEnded($call));
        }
    }

    /**
     * Handle failed status.
     */
    protected function handleFailed(WhatsAppCall $call, array $callData)
    {
        $reason = $callData['failure_reason'] ?? 'Unknown error';

        $call->markAsFailed($reason);

        Log::warning("Call failed", [
            'call_id' => $call->call_id,
            'reason' => $reason,
        ]);

        // Dispatch event
        event(new \App\Events\CallFailed($call));
    }

    /**
     * Handle rejected status.
     */
    protected function handleRejected(WhatsAppCall $call)
    {
        $call->markAsRejected();

        Log::info("Call rejected", [
            'call_id' => $call->call_id,
        ]);

        // Dispatch event
        event(new \App\Events\CallRejected($call));
    }

    /**
     * Handle missed/no answer status.
     */
    protected function handleMissed(WhatsAppCall $call)
    {
        $call->markAsMissed();

        Log::info("Call missed", [
            'call_id' => $call->call_id,
        ]);

        // Dispatch event
        event(new \App\Events\CallMissed($call));
    }

    /**
     * Ensure contact and conversation exist for inbound calls.
     */
    protected function ensureContactAndConversation(Team $team, WhatsAppCall $call, string $phoneNumber)
    {
        // Normalize phone number
        $normalizedPhone = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);

        // Find or create contact
        $contact = Contact::firstOrCreate(
            [
                'team_id' => $team->id,
                'phone_number' => $normalizedPhone,
            ],
            [
                'name' => $normalizedPhone,
            ]
        );

        // Create conversation if needed
        $conversationService = new ConversationService();
        $conversation = $conversationService->ensureActiveConversation($contact);

        // Update call with contact and conversation
        $call->update([
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Verify webhook signature from WhatsApp.
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        // Get the signature from headers
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        // Get app secret from config
        $appSecret = config('whatsapp.app_secret');

        if (!$appSecret) {
            Log::warning("WhatsApp app_secret not configured");
            return true; // Allow in development if not configured
        }

        // Calculate expected signature
        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        // Compare signatures
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle webhook verification (GET request from WhatsApp).
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub.mode');
        $token = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        $verifyToken = config('whatsapp.webhook_verify_token', 'whatsapp_calling_webhook');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info("WhatsApp call webhook verified successfully");
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning("WhatsApp call webhook verification failed");
        return response('Forbidden', 403);
    }
}

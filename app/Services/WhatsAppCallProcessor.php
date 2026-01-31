<?php

namespace App\Services;

use App\Models\Team;
use App\Models\WhatsAppCall;
use App\Models\Contact;
use App\Services\ConversationService;
use App\Events\CallOffered;
use App\Events\CallRinging;
use App\Events\CallAnswered;
use App\Events\CallEnded;
use App\Events\CallFailed;
use App\Events\CallRejected;
use App\Events\CallMissed;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WhatsAppCallProcessor
{
    /**
     * Process a batch of call events from a webhook.
     */
    public function process(Team $team, array $calls)
    {
        foreach ($calls as $callData) {
            $this->processSingleCall($team, $callData);
        }
    }

    /**
     * Process a single call event.
     */
    protected function processSingleCall(Team $team, array $callData)
    {
        $callId = $callData['id'] ?? null;
        $from = $callData['from'] ?? null;
        $to = $callData['to'] ?? null;
        $event = $callData['event'] ?? null; // connect, terminate, etc.
        $status = $callData['status'] ?? null; // COMPLETED, etc.
        $timestamp = $callData['timestamp'] ?? null;
        $direction = $callData['direction'] ?? null; // USER_INITIATED, etc.

        if (!$callId) {
            Log::warning("Call webhook missing call ID");
            return;
        }

        Log::info("Processing WhatsApp Call Event: {$event}", [
            'call_id' => $callId,
            'team_id' => $team->id,
            'from' => $from,
            'to' => $to,
            'event' => $event,
            'status' => $status
        ]);

        // Normalize direction
        $normalizedDirection = 'inbound';
        if ($direction === 'USER_INITIATED') {
            $normalizedDirection = 'inbound';
        } elseif ($from === $team->whatsapp_phone_number_id) {
            $normalizedDirection = 'outbound';
        }

        // Find or create call record
        $call = WhatsAppCall::where('call_id', $callId)->first();

        if (!$call) {
            // New call
            $call = WhatsAppCall::create([
                'call_id' => $callId,
                'team_id' => $team->id,
                'direction' => $normalizedDirection,
                'status' => 'initiated',
                'from_number' => $from,
                'to_number' => $to,
                'initiated_at' => $timestamp ? Carbon::createFromTimestamp($timestamp) : now(),
                'metadata' => array_intersect_key($callData, array_flip(['session', 'direction', 'session_id'])),
            ]);

            // Ensure contact exists for inbound calls
            if ($normalizedDirection === 'inbound') {
                $this->ensureContactAndConversation($team, $call, $from);
            }
        }

        // Handle Events
        if ($event === 'connect' || $event === 'connected') {
            $this->handleConnect($call, $callData);
        } elseif ($event === 'answered') {
            $this->handleAnswered($call, $callData);
        } elseif ($event === 'terminate') {
            $this->handleTerminate($call, $callData);
        } elseif ($status) {
            // Fallback for status-only updates if any
            if ($status === 'ANSWERED' || $status === 'IN_PROGRESS') {
                $this->handleAnswered($call, $callData);
            } else {
                $this->handleStatusUpdate($call, $status, $callData);
            }
        }
    }

    protected function handleConnect(WhatsAppCall $call, array $callData)
    {
        Log::debug("WhatsAppCallProcessor: Handling connect/connected event", [
            'call_id' => $call->call_id,
            'has_session' => isset($callData['session']),
            'sdp_type' => $callData['session']['sdp_type'] ?? 'N/A'
        ]);

        // Capture SDP offer for inbound calls
        $sdp = $callData['session']['sdp'] ?? $callData['session_data']['sdp'] ?? null;
        $sdpType = $callData['session']['sdp_type'] ?? $callData['session_data']['sdp_type'] ?? null;

        if ($sdp && $sdpType === 'offer') {
            $sanitizedSdp = \App\Services\SDPValidator::sanitize($sdp);

            $call->update([
                'status' => 'ringing',
                'metadata' => array_merge($call->metadata ?? [], ['sdp' => $sanitizedSdp])
            ]);

            // Record SDP offer received for quality tracking
            $call->recordSdpOfferReceived();

            Log::info("Dispatching CallOffered for inbound call (Offer captured): {$call->call_id}");
            event(new CallOffered($call->fresh())); // Use fresh to ensure metadata is reloaded
        } elseif ($sdp && $sdpType === 'answer') {
            // If it's an answer in the connect event (outbound answer)
            $this->handleAnswered($call, $callData);
        } else {
            // Generic connect without offer/answer? Maybe call ringing elsewhere
            if ($call->status === 'initiated' || $call->status === 'ringing') {
                $call->update(['status' => 'ringing']);
                Log::info("Dispatching CallRinging for call: {$call->call_id}");
                event(new CallRinging($call->fresh()));
            }
        }
    }

    protected function handleAnswered(WhatsAppCall $call, array $callData)
    {
        Log::info("Handling Call Answered: {$call->call_id}");

        if ($call->status !== 'in_progress') {
            $call->markAsAnswered();

            // Store SDP answer if present (from mobile/other client)
            $sdp = $callData['session']['sdp'] ?? $callData['session_data']['sdp'] ?? null;
            if ($sdp) {
                $call->update([
                    'metadata' => array_merge($call->metadata ?? [], ['answered_sdp' => $sdp])
                ]);
            }

            event(new CallAnswered($call->fresh()));
        }
    }

    protected function handleTerminate(WhatsAppCall $call, array $callData)
    {
        $status = $callData['status'] ?? 'COMPLETED';

        Log::info("Handling Call Terminate: {$status}", ['call_id' => $call->call_id]);

        switch ($status) {
            case 'COMPLETED':
                $call->markAsEnded();
                event(new CallEnded($call));
                break;
            case 'MISSED':
            case 'NO_ANSWER':
                $call->markAsMissed();
                event(new CallMissed($call));
                break;
            case 'REJECTED':
            case 'BUSY':
                $call->markAsRejected();
                event(new CallRejected($call));
                break;
            case 'FAILED':
            default:
                $call->markAsFailed($callData['failure_reason'] ?? 'Call terminated');
                event(new CallFailed($call));
                break;
        }

        // Trigger log to message thread
        try {
            $logService = new CallLogService();
            $logService->logCall($call);
        } catch (\Exception $e) {
            Log::error("Failed to log call to thread: " . $e->getMessage());
        }
    }

    protected function handleStatusUpdate(WhatsAppCall $call, string $status, array $callData)
    {
        // Additional status handling if needed (e.g. from older webhook versions)
        Log::info("Fallback Status Update: {$status}", ['call_id' => $call->call_id]);
    }

    protected function ensureContactAndConversation(Team $team, WhatsAppCall $call, string $phoneNumber)
    {
        try {
            // Normalize phone number to prevent duplicates
            $normalizedPhone = \App\Helpers\PhoneNumberHelper::normalize($phoneNumber);

            // Find or create contact
            $contact = Contact::where('team_id', $team->id)
                ->where('phone_number', $normalizedPhone)
                ->first();

            if (!$contact) {
                $contact = Contact::create([
                    'team_id' => $team->id,
                    'phone_number' => $normalizedPhone,
                    'name' => $normalizedPhone,
                    'opt_in_source' => 'whatsapp_call',
                ]);
            }

            // Create conversation if needed
            $conversationService = new ConversationService();
            $conversation = $conversationService->ensureActiveConversation($contact);

            // Update call with contact and conversation
            $call->update([
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to ensure contact/conv for call: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Services;

use App\Events\CallAnswered;
use App\Events\CallEnded;
use App\Events\CallFailed;
use App\Events\CallMissed;
use App\Events\CallOffered;
use App\Events\CallRejected;
use App\Events\CallRinging;
use App\Helpers\PhoneNumberHelper;
use App\Jobs\MonitorCallTimeoutJob;
use App\Models\Contact;
use App\Models\Team;
use App\Models\WhatsAppCall;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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

        if (! $callId) {
            Log::warning('Call webhook missing call ID');

            return;
        }

        Log::info("Processing WhatsApp Call Event: {$event}", [
            'call_id' => $callId,
            'team_id' => $team->id,
            'from' => $from,
            'to' => $to,
            'event' => $event,
            'status' => $status,
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

        // --- CALL STATUS STICKINESS (UC-SAFE-07) ---
        $callRanks = [
            'initiated' => 0,
            'ringing' => 1,
            'in_progress' => 2,
            'completed' => 3, // Terminal
            'failed' => 3, // Terminal
            'rejected' => 3, // Terminal
            'missed' => 3, // Terminal
        ];

        if ($call) {
            $currentRank = $callRanks[$call->status] ?? 0;

            // If new data suggests an event/status, check if it's a retrograde move
            // Events roughly map to ranks: connect=rings, answered=in_progress, terminate=terminal
            $newRank = $currentRank; // Default
            $eventNormalized = strtolower($event ?? '');

            if ($eventNormalized === 'connect' || $eventNormalized === 'connected') {
                $newRank = 1;
            }
            if ($eventNormalized === 'answered' || strtolower($status ?? '') === 'in_progress') {
                $newRank = 2;
            }
            if ($eventNormalized === 'terminate' || in_array(strtolower($status ?? ''), ['completed', 'failed', 'rejected', 'missed'])) {
                $newRank = 3;
            }

            if ($newRank < $currentRank && $currentRank >= 3) {
                Log::info("WhatsAppCallProcessor: Ignoring retrograde event '{$event}' for terminal call {$callId}");

                return;
            }
        }

        if (! $call) {
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
        $eventNormalized = strtolower($event ?? '');
        $statusNormalized = strtolower($status ?? '');

        if ($eventNormalized === 'connect' || $eventNormalized === 'connected') {
            $this->handleConnect($call, $callData);
        } elseif ($eventNormalized === 'answered' || $eventNormalized === 'accepted') {
            $this->handleAnswered($call, $callData);
        } elseif ($eventNormalized === 'terminate') {
            $this->handleTerminate($call, $callData);
        } elseif ($statusNormalized) {
            // Fallback for status-only updates. Meta's status value is "accepted";
            // "answered"/"in_progress" kept for backward compatibility.
            if (in_array($statusNormalized, ['accepted', 'answered', 'in_progress'], true)) {
                $this->handleAnswered($call, $callData);
            } else {
                $this->handleStatusUpdate($call, $status, $callData);
            }
        }
    }

    protected function handleConnect(WhatsAppCall $call, array $callData)
    {
        try {
            Log::channel('whatsapp')->info("Handling Connect: {$call->call_id}");
        } catch (\Exception $e) {
            // Silently fail
        }

        Log::info('WhatsAppCallProcessor: Handling connect/connected event', [
            'call_id' => $call->call_id,
            'has_session' => isset($callData['session']),
            'sdp_type' => $callData['session']['sdp_type'] ?? 'N/A',
        ]);

        // Capture SDP offer/answer
        $sdp = $callData['session']['sdp'] ?? $callData['session_data']['sdp'] ?? null;
        $sdpType = strtolower($callData['session']['sdp_type'] ?? $callData['session_data']['sdp_type'] ?? '');

        if ($sdp && $sdpType === 'offer') {
            $sanitizedSdp = SDPValidator::sanitize($sdp);

            $call->update([
                'status' => 'ringing',
                'metadata' => array_merge($call->metadata ?? [], ['sdp' => $sanitizedSdp]),
            ]);

            // Record SDP offer received for quality tracking
            $call->recordSdpOfferReceived();

            Log::info("Dispatching CallOffered (Offer captured): {$call->call_id}");
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
        try {
            Log::channel('whatsapp')->info("Handling Call Answered: {$call->call_id}");
        } catch (\Exception $e) {
            // Silently fail
        }

        Log::info("Handling Call Answered: {$call->call_id}");

        if ($call->status !== 'in_progress') {
            $call->markAsAnswered();

            // Store SDP answer if present (from mobile/other client)
            $sdp = $callData['session']['sdp'] ?? $callData['session_data']['sdp'] ?? null;
            if ($sdp) {
                $call->update([
                    'metadata' => array_merge($call->metadata ?? [], ['answered_sdp' => $sdp]),
                ]);
            }

            // Notify CallService to mark agent as busy
            $callService = app(CallService::class)->setTeam($call->team);
            $callService->handleCallStarted($call);

            event(new CallAnswered($call->fresh()));
        }
    }

    protected function handleTerminate(WhatsAppCall $call, array $callData)
    {
        $status = strtolower($callData['status'] ?? 'completed');

        try {
            Log::channel('whatsapp')->info("Handling Terminate [{$status}]: {$call->call_id}");
        } catch (\Exception $e) {
            // Silently fail
        }

        Log::info("Handling Call Terminate: {$status}", ['call_id' => $call->call_id]);

        switch ($status) {
            case 'completed':
                $call->markAsEnded();
                event(new CallEnded($call));
                break;
            case 'missed':
            case 'no_answer':
                $call->markAsMissed();
                event(new CallMissed($call));
                break;
            case 'rejected':
            case 'busy':
                $call->markAsRejected();
                event(new CallRejected($call));
                break;
            case 'failed':
            default:
                $call->markAsFailed($callData['failure_reason'] ?? 'Call terminated');
                event(new CallFailed($call));
                break;
        }

        // Notify CallService to handle agent cooldown/availability
        $callService = app(CallService::class)->setTeam($call->team);
        $callService->handleCallEnded($call);

        // Record terminal event for safeguards (missed/failed)
        if (in_array($status, ['missed', 'failed', 'no_answer', 'rejected'])) {
            $safeguardService = new CallSafeguardService;
            $type = in_array($status, ['missed', 'no_answer', 'rejected']) ? 'missed' : 'failed';
            $safeguardService->recordEvent($call->team, $type);
        }

        // Trigger log to message thread
        try {
            $logService = new CallLogService;
            $logService->logCall($call);
        } catch (\Exception $e) {
            Log::error('Failed to log call to thread: '.$e->getMessage());
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
            $normalizedPhone = PhoneNumberHelper::normalize($phoneNumber);

            // Find or create contact
            $contact = Contact::where('team_id', $team->id)
                ->where('phone_number', $normalizedPhone)
                ->first();

            if (! $contact) {
                $contact = Contact::create([
                    'team_id' => $team->id,
                    'phone_number' => $normalizedPhone,
                    'name' => $normalizedPhone,
                    'opt_in_source' => 'whatsapp_call',
                ]);
            }

            // Create conversation if needed
            $conversationService = new ConversationService;
            $conversation = $conversationService->ensureActiveConversation($contact);

            // Update call with contact and conversation
            $call->update([
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
            ]);

            // Implicit call-back permission: a user calling the business opens a
            // callback window (per Meta). Mirror it in our permission ledger.
            $permissionService = new CallPermissionService;
            $permission = $permissionService->trackPermissionRequest($contact, $team, $team->whatsapp_phone_number_id);
            $permissionService->grantPermission($permission);

            // Route call to an agent if not already assigned
            if (! $call->agent_id) {
                $routingService = new CallRoutingService($team);
                $routingResult = $routingService->findAgent($contact);

                if ($routingResult['agent']) {
                    $call->update(['agent_id' => $routingResult['agent']->id]);

                    // Set sticky assignment if contact is not currently assigned
                    if (! $contact->assigned_to) {
                        $contact->update(['assigned_to' => $routingResult['agent']->id]);
                    }

                    Log::info('Inbound call routed to agent', [
                        'call_id' => $call->call_id,
                        'agent_id' => $routingResult['agent']->id,
                        'method' => $routingResult['method'],
                    ]);
                } elseif (isset($routingResult['action']) && $routingResult['action'] === 'auto_reply') {
                    // Trigger fallback auto-reply if needed
                    Log::info('Inbound call triggered fallback action', [
                        'call_id' => $call->call_id,
                        'action' => 'auto_reply',
                    ]);
                }

                // Dispatch timeout monitor delayed by configured timeout
                MonitorCallTimeoutJob::dispatch($call->id)
                    ->delay(now()->addSeconds($team->getCallRoutingConfig()['ring_timeout_seconds'] ?? config('whatsapp.calling.ring_timeout_seconds', 30)));
            }
        } catch (\Exception $e) {
            Log::error('Failed to ensure contact/conv/routing for call: '.$e->getMessage());
        }
    }
}

<?php

namespace App\Livewire\Calls;

use App\Models\Contact;
use App\Models\WhatsAppCall;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class CallOverlay extends Component
{
    public $callId = null;
    public $status = 'idle'; // idle, ringing, active, ended
    public $direction = 'outbound';
    public $contactName = '';
    public $contactAvatar = '';
    public $startTime = null;
    public $offerSdp = null; // Store incoming offer SDP
    public $isLocked = false; // Prevents double actions
    public $occupiedBy = null; // Name of agent who took the call
    public $teamId;

    public function getListeners()
    {
        $teamId = auth()->check() ? auth()->user()->current_team_id : null;

        if (!$teamId) {
            return ['initiate-whatsapp-call' => 'handleInitiation'];
        }

        return [
            'initiate-whatsapp-call' => 'handleInitiation',
            "echo-private:teams.{$teamId},.call.offered" => 'handleOffered',
            "echo-private:teams.{$teamId},.call.ringing" => 'handleRinging',
            "echo-private:teams.{$teamId},.call.answered" => 'handleAnswered',
            "echo-private:teams.{$teamId},.call.ended" => 'handleEnded',
            "echo-private:teams.{$teamId},.call.missed" => 'handleEnded',
            "echo-private:teams.{$teamId},.call.rejected" => 'handleEnded',
            "echo-private:teams.{$teamId},.call.failed" => 'handleFailed',
            "echo-private:teams.{$teamId},.call.taken" => 'handleCallTaken',
        ];
    }

    public function mount()
    {
        $team = auth()->user()->currentTeam;

        if (!$team) {
            return;
        }

        $this->teamId = $team->id;

        // Recovery: Check if there's an active call for this team that involves the current user (if routing is implemented)
        // For now, any active call for the team will be shown in the overlay
        $activeCall = WhatsAppCall::where('team_id', $this->teamId)
            ->whereIn('status', ['initiated', 'ringing', 'in_progress'])
            ->latest()
            ->first();

        if ($activeCall) {
            $this->callId = $activeCall->call_id;
            $this->status = $activeCall->status === 'in_progress' ? 'active' : $activeCall->status;
            $this->direction = $activeCall->direction;
            $this->contactName = $activeCall->contact->name ?? $activeCall->from_number;
            $this->contactAvatar = "https://api.dicebear.com/9.x/micah/svg?seed=" . $this->contactName;
            $this->startTime = $activeCall->answered_at?->timestamp;

            // For recovery, we need to know if we have an offer or an answer
            // Inbound calls always have metadata['sdp'] (the offer)
            // Outbound calls that were answered have metadata['answered_sdp']
            $this->offerSdp = $activeCall->metadata['answered_sdp'] ?? $activeCall->metadata['sdp'] ?? null;
        }
    }

    public function handleInitiation($data)
    {
        Log::info("CallOverlay: Received initiate-whatsapp-call event", ['data' => $data]);

        // Robustly get contact ID and phone number
        $contactId = $data['contact_id'] ?? null;
        $phoneNumber = $data['phone_number'] ?? null;

        if (!$phoneNumber && $contactId) {
            $contact = Contact::find($contactId);
            $phoneNumber = $contact->phone_number ?? null;
        }

        // Instead of calling API directly (which fails without SDP), we tell the browser to generate SDP
        // Using named arguments to ensure event.detail is a flat object in JS
        $this->dispatch(
            'trigger-sdp-offer',
            phone_number: $phoneNumber,
            contact_id: $contactId
        );
    }

    public function initiateWhatsAppCall($phoneNumber, $contactId, $sdp = null)
    {
        Log::info("CallOverlay: initiateWhatsAppCall requested", [
            'to' => $phoneNumber,
            'contact_id' => $contactId,
            'has_sdp' => !empty($sdp)
        ]);

        $teamId = auth()->user()?->currentTeam?->id;
        $contact = $teamId && $contactId
            ? Contact::where('team_id', $teamId)->find($contactId)
            : null;

        // Fallback if phoneNumber is missing
        if (!$phoneNumber && $contact) {
            $phoneNumber = $contact->phone_number;
        }

        if (!$phoneNumber) {
            Log::error("CallOverlay: Phone number is missing", ['contact_id' => $contactId]);
            $this->handleFailed(['message' => 'Phone number is missing.']);
            return;
        }

        $this->status = 'ringing';
        $this->direction = 'outbound';
        $this->contactName = $contact->name ?? $phoneNumber;
        $this->contactAvatar = "https://api.dicebear.com/9.x/micah/svg?seed=" . ($contact->name ?? $phoneNumber);
        $this->startTime = null;

        try {
            if (!auth()->user()->currentTeam) {
                throw new \Exception('No team selected.');
            }
            $whatsappService = new \App\Services\WhatsAppService(auth()->user()->currentTeam);
            $response = $whatsappService->initiateCall((string) $phoneNumber, $sdp);

            if ($response['success']) {
                $this->callId = $response['data']['id'] ?? $response['data']['calls'][0]['id'] ?? null;
                Log::info("CallOverlay: Call initiate-request successful", ['call_id' => $this->callId]);
            } else {
                $this->handleFailed(['message' => $response['message'] ?? 'Failed to initiate call']);
            }
        } catch (\Exception $e) {
            Log::error("CallOverlay: Failed to initiate call", ['error' => $e->getMessage()]);
            $this->handleFailed(['message' => $e->getMessage()]);
        }
    }

    public function handleOffered($event)
    {
        Log::info("CallOverlay: Received CallOffered event", ['event' => $event]);

        $this->callId = $event['call_id'] ?? null;
        $this->status = 'ringing';
        $this->direction = $event['direction'] ?? 'inbound';

        $this->contactName = $event['contact_name'] ?? $event['from'] ?? 'Unknown';
        $this->contactAvatar = $event['contact_avatar'] ?? "https://api.dicebear.com/9.x/micah/svg?seed=" . $this->contactName;
        $this->startTime = null;

        // Capture SDP from the event (could be a call offer or a re-negotiation offer)
        $this->offerSdp = $event['call']['metadata']['sdp'] ?? $event['sdp'] ?? null;

        if ($this->direction === 'inbound') {
            $this->dispatch('play-ringing-sound');
        }
    }

    public function handleRinging($event)
    {
        $callId = $event['call_id'] ?? $event['call']['call_id'] ?? null;
        if ($this->callId && $callId && $callId !== $this->callId) {
            return;
        }

        if ($this->status === 'idle') {
            $this->handleOffered($event);
        }
    }

    public function handleAnswered($event)
    {
        Log::info("CallOverlay: Received CallAnswered event", ['event' => $event]);

        $callData = $event['call'] ?? $event;
        $callId = $callData['call_id'] ?? null;

        if ($this->callId && $callId && $callId !== $this->callId) {
            return;
        }

        // If we are idle, this call isn't for us (another agent answered their own outbound call)
        if ($this->status === 'idle') {
            return;
        }

        $this->status = 'active';

        // If the server has an answer SDP (e.g. from mobile), we need to send it to the JS PeerConnection
        $answeredSdp = $callData['metadata']['answered_sdp'] ?? null;
        if ($answeredSdp) {
            $this->offerSdp = $answeredSdp;
            $this->dispatch('set-remote-answer', ['sdp' => $answeredSdp]);
        }

        // Set start time from event or now
        if (isset($callData['answered_at'])) {
            $this->startTime = \Carbon\Carbon::parse($callData['answered_at'])->timestamp;
        } else {
            $this->startTime = now()->timestamp;
        }
    }

    public function handleEnded($event)
    {
        $callData = $event['call'] ?? $event;
        $callId = $callData['call_id'] ?? null;

        if ($this->callId && $callId && $callId !== $this->callId) {
            return;
        }

        // Only show ended state if we were actually in a call
        if ($this->status === 'idle') {
            return;
        }

        $this->status = 'ended';
        $this->dispatch('call-stopped'); // For local JS cleanup

        // Auto-close after 3 seconds
        $this->dispatch('auto-hide-overlay');
    }

    public function handleFailed($event)
    {
        $callData = $event['call'] ?? $event;
        $callId = $callData['call_id'] ?? null;

        if ($this->callId && $callId && $callId !== $this->callId) {
            return;
        }

        if ($this->status === 'idle') {
            return;
        }

        $this->status = 'ended';
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Call sequence failed.'
        ]);
        $this->resetOverlay();
    }

    public function handleCallTaken($event)
    {
        $callId = $event['call_id'] ?? null;
        if ($this->callId && $callId && $callId !== $this->callId) {
            return;
        }

        // Another agent answered
        $agentName = $event['agent_name'] ?? 'Another Agent';

        // Don't lock if WE are the ones who took it
        if (auth()->check() && auth()->user()->name === $agentName) {
            return;
        }

        $this->occupiedBy = $agentName;
        $this->isLocked = true;

        // Auto-close after notification
        $this->dispatch('auto-hide-overlay');
    }

    public function syncCallState($data)
    {
        // This is called by JS BroadcastChannel to sync state across tabs of the SAME agent
        $this->status = $data['status'];
        $this->startTime = $data['startTime'] ?? null;
        $this->contactName = $data['contactName'] ?? '';
        $this->contactAvatar = $data['contactAvatar'] ?? '';
        $this->offerSdp = $data['offerSdp'] ?? $this->offerSdp; // Recover SDP if synced

        if ($this->status === 'ended') {
            $this->dispatch('auto-hide-overlay');
        }
    }

    public function answerCall($answerSdp = null)
    {
        if (!$this->callId)
            return;

        try {
            $team = auth()->user()->currentTeam;
            if (!$team) {
                throw new \Exception('No team selected.');
            }
            $whatsappService = new \App\Services\WhatsAppService($team);

            $session = null;
            if ($answerSdp) {
                $session = [
                    'sdp' => $answerSdp,
                    'sdp_type' => 'answer'
                ];
            }

            $call = \App\Models\WhatsAppCall::where('call_id', $this->callId)
                ->where('team_id', $team->id)
                ->first();
            $phoneNumberId = $call ? ($call->metadata['phone_number_id'] ?? null) : null;

            if ($session) {
                $preAccept = $whatsappService->preAcceptCall($this->callId, $session, $phoneNumberId);
                Log::info("CallOverlay: Meta Pre-Accept Response", ['response' => $preAccept]);
            }

            $response = $whatsappService->answerCall($this->callId, $session, $phoneNumberId);

            Log::info("CallOverlay: Meta Answer Response", ['response' => $response]);

            if ($response['success']) {
                $this->status = 'active';
                $this->startTime = now()->timestamp;
            } else {
                $errorMessage = $response['message'] ?? $response['error'] ?? 'Unknown error';
                $this->dispatch('notify', ['type' => 'error', 'message' => "Meta refused answer: " . $errorMessage]);
                Log::error("CallOverlay: Meta refused answer", ['message' => $errorMessage]);
            }
        } catch (\Exception $e) {
            Log::error("CallOverlay: Answer Exception", ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function recordConnectionEstablished($iceCandidatesCount = 0, $connectionType = null)
    {
        if (!$this->callId)
            return;

        try {
            $team = auth()->user()->currentTeam;
            if (!$team)
                return;

            $call = \App\Models\WhatsAppCall::where('call_id', $this->callId)
                ->where('team_id', $team->id)
                ->first();

            if ($call && $call->qualityMetric) {
                $call->qualityMetric->update([
                    'connection_established_at' => now(),
                    'ice_candidates_count' => $iceCandidatesCount,
                    'connection_type' => $connectionType,
                    'webrtc_state' => 'connected',
                ]);

                // Calculate connection latency
                if ($call->qualityMetric->sdp_answer_sent_at) {
                    $latency = $call->qualityMetric->sdp_answer_sent_at->diffInMilliseconds(now());
                    $call->qualityMetric->update(['connection_latency_ms' => $latency]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to record connection established', [
                'call_id' => $this->callId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function rejectCall()
    {
        if (!$this->callId)
            return;

        try {
            $team = auth()->user()->currentTeam;
            if (!$team)
                return;
            $whatsappService = new \App\Services\WhatsAppService($team);
            $response = $whatsappService->rejectCall($this->callId);

            if ($response['success']) {
                $this->resetOverlay();
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
            $this->resetOverlay();
        }
    }

    public function endCall()
    {
        if (!$this->callId) {
            $this->handleEnded([]);
            return;
        }

        try {
            $team = auth()->user()->currentTeam;
            if (!$team)
                return;
            $whatsappService = new \App\Services\WhatsAppService($team);
            $whatsappService->endCall($this->callId);
            $this->handleEnded([]);
        } catch (\Exception $e) {
            $this->handleEnded([]);
        }
    }

    public function resetOverlay()
    {
        $this->status = 'idle';
        $this->callId = null;
        $this->startTime = null;
        $this->isLocked = false;
        $this->occupiedBy = null;
    }

    public function render()
    {
        return view('livewire.calls.call-overlay');
    }
}

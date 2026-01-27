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
    public $isLocked = false; // Prevents double actions
    public $occupiedBy = null; // Name of agent who took the call
    public $teamId;

    public function getListeners()
    {
        return [
            'initiate-whatsapp-call' => 'handleInitiation',
            "echo-private:teams.{$this->teamId},.call.offered" => 'handleOffered',
            "echo-private:teams.{$this->teamId},.call.ringing" => 'handleRinging',
            "echo-private:teams.{$this->teamId},.call.answered" => 'handleAnswered',
            "echo-private:teams.{$this->teamId},.call.ended" => 'handleEnded',
            "echo-private:teams.{$this->teamId},.call.failed" => 'handleFailed',
            "echo-private:teams.{$this->teamId},.call.taken" => 'handleCallTaken',
        ];
    }

    public function mount()
    {
        $team = auth()->user()->currentTeam;
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
        }
    }

    public function handleInitiation($data)
    {
        $contact = Contact::find($data['contact_id']);

        $this->status = 'ringing';
        $this->direction = 'outbound';
        $this->contactName = $contact->name ?? $data['phone_number'];
        $this->contactAvatar = "https://api.dicebear.com/9.x/micah/svg?seed=" . ($contact->name ?? $data['phone_number']);
        $this->startTime = null;

        // In a real app, we'd get a call ID from the service
        // For simulation, we'll just track it locally
    }

    public function handleOffered($event)
    {
        Log::info("CallOverlay: Received CallOffered event", ['event' => $event]);

        $this->callId = $event['call_id'];
        $this->status = 'ringing';
        $this->direction = $event['direction'] ?? 'inbound';
        $this->contactName = $event['from'] ?? 'Unknown Caller';
        $this->contactAvatar = "https://api.dicebear.com/9.x/micah/svg?seed=" . $this->contactName;
        $this->startTime = null;

        if ($this->direction === 'inbound') {
            $this->dispatch('play-ringing-sound');
        }
    }

    public function handleRinging($event)
    {
        if ($this->status === 'idle') {
            $this->handleOffered($event);
        }
    }

    public function handleAnswered($event)
    {
        $this->status = 'active';
        $this->startTime = now()->timestamp;
    }

    public function handleEnded($event)
    {
        $this->status = 'ended';
        $this->dispatch('call-stopped'); // For local JS cleanup

        // Auto-close after 3 seconds
        $this->dispatch('auto-hide-overlay');
    }

    public function handleFailed($event)
    {
        $this->status = 'ended';
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Call sequence failed.'
        ]);
        $this->resetOverlay();
    }

    public function handleCallTaken($event)
    {
        // Another agent answered
        $this->occupiedBy = $event['agent_name'] ?? 'Another Agent';
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

        if ($this->status === 'ended') {
            $this->dispatch('auto-hide-overlay');
        }
    }

    public function answerCall()
    {
        if (!$this->callId)
            return;

        try {
            $team = auth()->user()->currentTeam;
            $whatsappService = new \App\Services\WhatsAppService($team);
            $response = $whatsappService->answerCall($this->callId);

            if ($response['success']) {
                $this->status = 'active';
                $this->startTime = now()->timestamp;
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function rejectCall()
    {
        if (!$this->callId)
            return;

        try {
            $team = auth()->user()->currentTeam;
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

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

    protected $listeners = [
        'initiate-whatsapp-call' => 'handleInitiation',
        'echo-private:teams.{teamId},call.answered' => 'handleAnswered',
        'echo-private:teams.{teamId},call.ended' => 'handleEnded',
        'echo-private:teams.{teamId},call.failed' => 'handleFailed',
        'echo-private:teams.{teamId},call.taken' => 'handleCallTaken',
    ];

    public function mount()
    {
        $this->teamId = auth()->user()->currentTeam->id;
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

    public function endCall()
    {
        // Integration with Service would go here
        $this->handleEnded([]);
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

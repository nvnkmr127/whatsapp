<?php

namespace App\Livewire\Calls;

use App\Models\Contact;
use App\Services\CallService;
use App\Services\WhatsAppService;
use Livewire\Component;

class CallControls extends Component
{
    public Contact $contact;
    public $activeCall = null;
    public $isInitiating = false;

    protected $listeners = [
        'echo-private:team.{teamId},call.ringing' => 'handleCallRinging',
        'echo-private:team.{teamId},call.answered' => 'handleCallAnswered',
        'echo-private:team.{teamId},call.ended' => 'handleCallEnded',
        'echo-private:team.{teamId},call.failed' => 'handleCallFailed',
    ];

    public function mount(Contact $contact)
    {
        $this->contact = $contact;
        $this->checkActiveCall();
    }

    public function checkActiveCall()
    {
        $team = auth()->user()->currentTeam;

        $this->activeCall = \App\Models\WhatsAppCall::where('team_id', $team->id)
            ->where('contact_id', $this->contact->id)
            ->whereIn('status', ['initiated', 'ringing', 'in_progress'])
            ->latest()
            ->first();
    }

    public function initiateCall()
    {
        $this->isInitiating = true;

        try {
            $team = auth()->user()->currentTeam;
            $callService = new CallService($team);

            $response = $callService->initiateCall($this->contact->phone_number);

            if ($response['success']) {
                $this->checkActiveCall();
                $this->dispatch('call-initiated', ['message' => 'Call initiated successfully']);
            } else {
                $this->dispatch('call-error', ['message' => $response['error'] ?? 'Failed to initiate call']);
            }
        } catch (\Exception $e) {
            $this->dispatch('call-error', ['message' => $e->getMessage()]);
        } finally {
            $this->isInitiating = false;
        }
    }

    public function answerCall()
    {
        if (!$this->activeCall) {
            return;
        }

        try {
            $team = auth()->user()->currentTeam;
            $whatsappService = new WhatsAppService($team);

            $response = $whatsappService->answerCall($this->activeCall->call_id);

            if ($response['success']) {
                $this->checkActiveCall();
                $this->dispatch('call-answered', ['message' => 'Call answered']);
            }
        } catch (\Exception $e) {
            $this->dispatch('call-error', ['message' => $e->getMessage()]);
        }
    }

    public function rejectCall()
    {
        if (!$this->activeCall) {
            return;
        }

        try {
            $team = auth()->user()->currentTeam;
            $whatsappService = new WhatsAppService($team);

            $response = $whatsappService->rejectCall($this->activeCall->call_id);

            if ($response['success']) {
                $this->activeCall = null;
                $this->dispatch('call-rejected', ['message' => 'Call rejected']);
            }
        } catch (\Exception $e) {
            $this->dispatch('call-error', ['message' => $e->getMessage()]);
        }
    }

    public function endCall()
    {
        if (!$this->activeCall) {
            return;
        }

        try {
            $team = auth()->user()->currentTeam;
            $whatsappService = new WhatsAppService($team);

            $response = $whatsappService->endCall($this->activeCall->call_id);

            if ($response['success']) {
                $this->activeCall = null;
                $this->dispatch('call-ended', ['message' => 'Call ended']);
            }
        } catch (\Exception $e) {
            $this->dispatch('call-error', ['message' => $e->getMessage()]);
        }
    }

    // Real-time event handlers
    public function handleCallRinging($event)
    {
        if ($event['contact_id'] == $this->contact->id) {
            $this->checkActiveCall();
        }
    }

    public function handleCallAnswered($event)
    {
        $this->checkActiveCall();
    }

    public function handleCallEnded($event)
    {
        $this->activeCall = null;
    }

    public function handleCallFailed($event)
    {
        $this->activeCall = null;
        $this->dispatch('call-error', ['message' => $event['reason'] ?? 'Call failed']);
    }

    public function render()
    {
        return view('livewire.calls.call-controls');
    }
}

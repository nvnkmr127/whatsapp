<?php

namespace App\Livewire\Calls;

use App\Models\Contact;
use App\Services\CallService;
use App\Services\CallEligibilityService;
use App\Services\WhatsAppService;
use Livewire\Component;

class CallControls extends Component
{
    public Contact $contact;
    public $activeCall = null;
    public $isInitiating = false;
    public $eligibility = null;
    public $showEligibilityDetails = false;
    public $teamId;

    public function getListeners()
    {
        return [
            "echo-private:teams.{$this->teamId},.call.offered" => 'handleCallOffered',
            "echo-private:teams.{$this->teamId},.call.ringing" => 'handleCallRinging',
            "echo-private:teams.{$this->teamId},.call.answered" => 'handleCallAnswered',
            "echo-private:teams.{$this->teamId},.call.ended" => 'handleCallEnded',
            "echo-private:teams.{$this->teamId},.call.failed" => 'handleCallFailed',
        ];
    }

    public function mount(Contact $contact)
    {
        $this->contact = $contact;
        $this->teamId = auth()->user()->currentTeam->id;
        $this->checkActiveCall();
        $this->checkEligibility();
    }

    public function checkEligibility()
    {
        $team = auth()->user()->currentTeam;
        $eligibilityService = new CallEligibilityService($team);

        // Determine trigger type and context
        $triggerType = 'user_initiated'; // Default, can be changed based on UI action
        $context = [
            'trigger_source' => 'in_app_action',
            'trigger_message' => 'User clicked call button',
            'conversation_id' => $this->contact->conversations()->latest()->first()?->id,
        ];

        $this->eligibility = $eligibilityService->checkEligibility(
            $this->contact,
            $triggerType,
            $context
        );
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
        // Re-check eligibility before initiating
        $this->checkEligibility();

        if (!$this->eligibility['eligible']) {
            $this->dispatch('call-error', ['message' => $this->eligibility['user_message']]);
            return;
        }

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
    public function handleCallOffered($event)
    {
        if ($event['contact_id'] == $this->contact->id) {
            $this->checkActiveCall();
        }
    }

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

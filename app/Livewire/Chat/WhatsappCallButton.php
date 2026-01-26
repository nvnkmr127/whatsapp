<?php

namespace App\Livewire\Chat;

use App\Models\Contact;
use App\Services\CallEligibilityService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class WhatsappCallButton extends Component
{
    public Contact $contact;
    public $eligibility = null;
    public $isLoading = true;

    public function mount(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function checkEligibility()
    {
        $this->isLoading = true;

        try {
            $service = new CallEligibilityService(auth()->user()->currentTeam);
            // Defaulting trigger to 'user_initiated' for manual clicks
            $this->eligibility = $service->checkEligibility($this->contact, 'user_initiated');
        } catch (\Exception $e) {
            Log::error("Call eligibility check failed: " . $e->getMessage());
            $this->eligibility = [
                'eligible' => false,
                'user_message' => 'Unable to verify eligibility.',
                'block_reason' => 'ERROR'
            ];
        }

        $this->isLoading = false;
    }

    public function initiateCall()
    {
        if (!$this->eligibility || !$this->eligibility['eligible']) {
            $this->checkEligibility();
            if (!$this->eligibility['eligible']) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => $this->eligibility['user_message'] ?? 'Not eligible to call.'
                ]);
                return;
            }
        }

        // Logic to actually initiate the call
        // This would typically emit an event to a global caller component or call an API
        $this->dispatch('initiate-whatsapp-call', [
            'contact_id' => $this->contact->id,
            'phone_number' => $this->contact->phone_number
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Call sequence initiated.'
        ]);
    }

    public function render()
    {
        return view('livewire.chat.whatsapp-call-button');
    }
}

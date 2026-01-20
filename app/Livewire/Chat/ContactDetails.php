<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use Livewire\Component;

class ContactDetails extends Component
{
    public $conversationId;
    public $conversation;
    public $contact;

    public $newNoteBody = '';

    public function mount($conversationId)
    {
        $this->conversationId = $conversationId;
        $this->loadData();
    }

    public function loadData()
    {
        $this->conversation = Conversation::with([
            'contact.tags',
            'contact.attributedMessages.attributedCampaign',
            'notes.user',
            'assignee'
        ])->find($this->conversationId);
        $this->contact = $this->conversation->contact;
    }

    public function assignToSelf()
    {
        if ($this->conversation) {
            $this->conversation->update(['assigned_to' => auth()->id()]);
            $this->loadData();
        }
    }

    public function unassign()
    {
        if ($this->conversation) {
            $this->conversation->update(['assigned_to' => null]);
            $this->loadData();
        }
    }

    public function addNote()
    {
        $this->validate(['newNoteBody' => 'required|string|max:1000']);

        if ($this->conversation) {
            $this->conversation->notes()->create([
                'user_id' => auth()->id(),
                'content' => $this->newNoteBody
            ]);

            $this->newNoteBody = '';
            $this->loadData();
        }
    }

    public function toggleOptIn(\App\Services\ConsentService $consentService)
    {
        if (!$this->conversation || !$this->contact)
            return;

        if ($this->contact->opt_in_status === 'opted_in') {
            $consentService->optOut($this->contact, 'MANUAL_AGENT', 'Agent toggled status in chat interface.');
        } else {
            $consentService->optIn($this->contact, 'MANUAL_AGENT', 'Agent toggled status in chat interface.');
        }

        $this->loadData();
    }

    public function render()
    {
        return view('livewire.chat.contact-details');
    }
}

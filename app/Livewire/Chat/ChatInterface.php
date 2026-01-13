<?php

namespace App\Livewire\Chat;

use App\Models\Contact;
use App\Models\Message;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatInterface extends Component
{
    public $contacts;
    public $selectedContact;
    public $messages = [];
    public $notes = [];
    public $newMessage = '';
    public $newNote = '';
    public $filter = 'all'; // all, mine, unassigned
    public $teamMembers = [];
    public $lastMessageId = 0;

    public function pollMessages()
    {
        $this->loadContacts();
        if ($this->selectedContact) {
            $this->loadMessages();
        }

        // Notification Logic
        $latest = Message::where('team_id', Auth::user()->currentTeam->id)
            ->where('direction', 'inbound')
            ->latest()
            ->first();

        if ($latest && $latest->id > $this->lastMessageId) {
            $this->lastMessageId = $latest->id;
            // Only notify if we haven't just loaded the page (assuming lastMessageId initiated at 0, skipping first poll? 
            // Better: Init ID on mount.
            $this->dispatch('play-notification');
        }
    }

    public function getListeners()
    {
        $teamId = Auth::user()->currentTeam->id;
        return [
            "echo-private:teams.{$teamId},MessageReceived" => 'refreshMessages',
        ];
    }

    public function mount()
    {
        $this->teamMembers = Auth::user()->currentTeam->allUsers();
        $this->loadContacts();

        // Init latest message tracking
        $latest = Message::where('team_id', Auth::user()->currentTeam->id)
            ->where('direction', 'inbound')
            ->latest()
            ->first();
        $this->lastMessageId = $latest ? $latest->id : 0;
    }

    public function loadContacts()
    {
        $query = Contact::where('team_id', Auth::user()->currentTeam->id)
            ->with([
                'assignedTo',
                'messages' => function ($q) {
                    $q->latest()->limit(1);
                }
            ]);

        if ($this->filter === 'mine') {
            $query->where('assigned_to', Auth::id());
        } elseif ($this->filter === 'unassigned') {
            $query->whereNull('assigned_to');
        }

        $this->contacts = $query->get()->sortByDesc(function ($contact) {
            return $contact->messages->first()?->created_at ?? $contact->created_at;
        });
    }

    public function updatedFilter()
    {
        $this->loadContacts();
    }

    public function selectContact($contactId)
    {
        $this->selectedContact = Contact::with('assignedTo')->findOrFail($contactId);
        $this->loadMessages();
        $this->loadNotes();
    }

    public function loadMessages()
    {
        if (!$this->selectedContact)
            return;

        $this->messages = Message::where('contact_id', $this->selectedContact->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark as read logic would go here
    }

    public function refreshMessages($event)
    {
        // If we have selected the contact who just sent a message, reload
        if ($this->selectedContact && $this->selectedContact->id === $event['message']['contact_id']) {
            $this->loadMessages();
        }

        // Always refresh contact list to show new message preview/time
        $this->loadContacts();
    }

    public function sendMessage(WhatsAppService $whatsapp)
    {
        $this->validate(['newMessage' => 'required|string']);

        if (!$this->selectedContact)
            return;

        $team = Auth::user()->currentTeam;

        // 1. Send via API
        try {
            $whatsapp->setTeam($team)->sendText($this->selectedContact->phone_number, $this->newMessage);
        } catch (\Exception $e) {
            $this->addError('newMessage', $e->getMessage());
            return;
        }

        // 2. Save to DB
        Message::create([
            'team_id' => $team->id,
            'contact_id' => $this->selectedContact->id,
            'direction' => 'outbound',
            'type' => 'text',
            'content' => $this->newMessage,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Update Contact SLA
        $this->selectedContact->update(['has_pending_reply' => false]);

        $this->newMessage = '';
        $this->loadMessages();
        $this->loadContacts(); // Re-sort list
    }

    // --- Assignment Logic ---

    public function assignToMe()
    {
        if ($this->selectedContact) {
            $this->selectedContact->update(['assigned_to' => Auth::id()]);
            $this->selectContact($this->selectedContact->id); // Refresh
        }
    }

    public function unassign()
    {
        if ($this->selectedContact) {
            $this->selectedContact->update(['assigned_to' => null]);
            $this->createSystemNote("Unassigned by " . Auth::user()->name);
            $this->selectContact($this->selectedContact->id); // Refresh
        }
    }

    public function assignToAgent($userId)
    {
        if ($this->selectedContact) {
            $this->selectedContact->update(['assigned_to' => $userId]);

            $agentName = \App\Models\User::find($userId)->name;
            $this->createSystemNote("Transferred to $agentName by " . Auth::user()->name);

            $this->selectContact($this->selectedContact->id); // Refresh
        }
    }

    private function createSystemNote($body)
    {
        $this->selectedContact->notes()->create([
            'team_id' => Auth::user()->currentTeam->id,
            'user_id' => Auth::id(), // Performed by
            'body' => $body,
            'type' => 'system'
        ]);
        $this->loadNotes();
    }

    // --- Notes Logic ---

    public function loadNotes()
    {
        if ($this->selectedContact) {
            $this->notes = $this->selectedContact->notes()->with('user')->get();
        }
    }

    public function addNote()
    {
        $this->validate(['newNote' => 'required|string']);

        if ($this->selectedContact) {
            $this->selectedContact->notes()->create([
                'team_id' => Auth::user()->currentTeam->id,
                'user_id' => Auth::id(),
                'body' => $this->newNote,
            ]);

            $this->newNote = '';
            $this->loadNotes();
        }
    }

    public function render()
    {
        return view('livewire.chat.chat-interface');
    }
}

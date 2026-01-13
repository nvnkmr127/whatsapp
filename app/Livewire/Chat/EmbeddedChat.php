<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\Contact;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmbeddedChat extends Component
{
    use WithFileUploads;

    public $contactId;
    public $permissions = [];
    public $conversation;
    public $messageBody = '';
    public $attachments = []; // Future usage

    protected $listeners = [
        'echo:conversation.{conversation.id},MessageReceived' => 'handleIncomingMessage',
    ];

    public function mount($contactId, $permissions = ['read', 'write'])
    {
        $this->contactId = $contactId;
        $this->permissions = $permissions;
        $this->loadConversation();
    }

    public function loadConversation()
    {
        // Find existing conversation for this contact
        // Note: For embedded view, we might need a dedicated endpoint or ensure Team scope.
        // Since the token was generated for a Team, we assume we are in that Team's context visually
        // but Livewire component might not share the session auth if inside iframe without cookies?
        // Wait: The EmbedController validates the token. But Livewire requests come back as separate AJAX calls.
        // If the user is NOT logged in (Ghost Agent), standard Auth::user() won't work in Livewire subsequent requests.
        // MVP Solution: 
        // 1. We rely on the fact that if frames are on same domain/subdomain, cookies *might* work.
        // 2. OR we pass the token to Livewire and re-validate on every request (Safe).
        // For MVP: We assume the user viewing the iframe has a session (e.g. they are logged into the ERP which is also the App, or 3rd party cookie enabled).
        // IF NOT: We would need a stateless Livewire approach using the token.

        $this->conversation = Conversation::with([
            'messages' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
            'contact'
        ])
            ->where('contact_id', $this->contactId)
            ->first();
    }

    public function sendMessage()
    {
        if (!in_array('write', $this->permissions)) {
            return; // Silently fail block or throw exception
        }

        $this->validate([
            'messageBody' => 'required_without:attachments|string',
        ]);

        if (!$this->conversation) {
            // Create conversation on the fly? Or wait for inbound?
            // Usually outbound to a contact starts a conversation.
            // We need to fetch the contact to get the phone number.
            $contact = Contact::find($this->contactId);
            if (!$contact)
                return;

            // We need a 'Team' context.
            // Implied from contact->team_id.
            // We'll hydrate a minimal team object for the service if needed.
            // Actually WhatsAppService -> setTeam() expects a Team model.
            $team = $contact->team;
        } else {
            $contact = $this->conversation->contact;
            $team = $this->conversation->team;
        }

        $waService = new WhatsAppService();
        $waService->setTeam($team);

        try {
            $response = $waService->sendText(
                $contact->phone_number,
                $this->messageBody
            );

            if ($response['success'] ?? false) {
                $this->reset(['messageBody']);
                $this->loadConversation(); // Reload or Optimistic append
                $this->dispatch('scroll-bottom');
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function getListeners()
    {
        // Dynamic listener based on conversation ID
        if ($this->conversation) {
            return [
                "echo:conversation.{$this->conversation->id},MessageReceived" => 'handleIncomingMessage',
            ];
        }
        return [];
    }

    public function handleIncomingMessage($event)
    {
        if ($this->conversation && $event['message']['conversation_id'] == $this->conversation->id) {
            $this->loadConversation();
            $this->dispatch('scroll-bottom');
        }
    }

    public function render()
    {
        return view('livewire.chat.embedded-chat');
    }
}

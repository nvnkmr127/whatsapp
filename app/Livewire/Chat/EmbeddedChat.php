<?php

namespace App\Livewire\Chat;

use App\Models\Contact;
use App\Models\Conversation;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmbeddedChat extends Component
{
    use WithFileUploads;

    public $contactId;

    public $permissions = [];

    public $token;

    public $conversation;

    public $messageBody = '';

    public $attachments = []; // Future usage

    protected $listeners = [
        'echo:conversation.{conversation.id},MessageReceived' => 'handleIncomingMessage',
    ];

    public function mount($contactId, $permissions = ['read', 'write'], $token = null)
    {
        $this->token = $token;
        $this->validateEmbedTokenOrAbort($permissions, $contactId);
        $this->loadConversation();
    }

    public function hydrate()
    {
        $this->validateEmbedTokenOrAbort($this->permissions, $this->contactId);
    }

    protected function validateEmbedTokenOrAbort($fallbackPermissions, $fallbackContactId)
    {
        if (! $this->token) {
            abort(403, 'Missing Token');
        }

        $payload = app(\App\Services\EmbedTokenService::class)->validateToken($this->token);
        if (! $payload) {
            abort(403, 'Invalid or Expired Token');
        }

        $this->contactId = $payload['contact_id'] ?? $fallbackContactId;
        if (! $this->contactId) {
            abort(403, 'Invalid Token Contact');
        }

        $this->permissions = $payload['permissions'] ?? $fallbackPermissions ?? ['read', 'write'];
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
            'contact',
        ])
            ->where('contact_id', $this->contactId)
            ->first();
    }

    public function sendMessage()
    {
        if (! in_array('write', $this->permissions)) {
            return; // Silently fail block or throw exception
        }

        $this->validate([
            'messageBody' => 'required_without:attachments|string',
        ]);

        if (! $this->conversation) {
            $contact = Contact::find($this->contactId);
            if (! $contact) {
                return;
            }
            $conversation = Conversation::firstOrCreate(
                ['team_id' => $contact->team_id, 'contact_id' => $contact->id],
                ['status' => 'open', 'last_message_at' => now()]
            );
            $this->conversation = $conversation;
        }

        $message = \App\Models\Message::create([
            'team_id' => $this->conversation->team_id,
            'contact_id' => $this->conversation->contact_id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'queued',
            'type' => 'text',
            'content' => $this->messageBody,
            'metadata' => ['source' => 'embedded_chat'],
        ]);

        \App\Jobs\SendMessageJob::dispatch($message);

        $this->reset(['messageBody']);
        $this->loadConversation();
        $this->dispatch('scroll-bottom');
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

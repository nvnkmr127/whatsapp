<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class MessageWindow extends Component
{
    use WithFileUploads;

    public $conversationId;
    public $messageBody = '';
    public $attachments = []; // For future media upload
    public $conversation;

    protected $listeners = [
        'echo:conversations,MessageReceived' => 'handleIncomingMessage',
    ];

    public function mount($conversationId)
    {
        $this->conversationId = $conversationId;
        $this->loadConversation();
    }

    public function loadConversation()
    {
        $this->conversation = Conversation::with([
            'messages' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
            'contact'
        ])->find($this->conversationId);

        // Mark as read logic could go here
    }

    public function sendMessage()
    {
        $this->validate([
            'messageBody' => 'required_without:attachments|string',
        ]);

        if (!$this->conversation)
            return;

        $waService = new WhatsAppService();
        $waService->setTeam(Auth::user()->currentTeam);

        // Send via API
        try {
            $response = $waService->sendText(
                $this->conversation->contact->phone_number,
                $this->messageBody
            );

            if ($response['success'] ?? false) {
                // Optimistically append (or wait for webhook?)
                // For better UX, we'll rely on the webhook to create the DB record and Echo to push it back.
                // But we can manually create a simplified outbound message record here if webhook latency is high.

                // Clear input
                $this->reset(['messageBody', 'attachments']);
            } else {
                session()->flash('error', 'Failed to send: ' . ($response['error']['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeConversation($reason = 'resolved')
    {
        if ($this->conversation) {
            $this->conversation->update([
                'status' => 'closed',
                'closed_at' => now(),
                'close_reason' => $reason
            ]);
            // Dispatch event for UI updates if needed
            $this->dispatch('conversation-closed');
            $this->loadConversation();
        }
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
        return view('livewire.chat.message-window');
    }
}

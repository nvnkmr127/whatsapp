<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AiAssistant extends Component
{
    public $conversationId;

    public $suggestions = [];

    public $isGenerating = false;

    public $showAiModal = false;

    protected $listeners = [
        'triggerAiAssistant' => 'generateSuggestions',
    ];

    public function generateSuggestions($conversationId = null)
    {
        if ($conversationId) {
            $this->conversationId = $conversationId;
        }

        if (! $this->conversationId) {
            return;
        }

        $this->showAiModal = true;
        $this->isGenerating = true;
        $this->suggestions = [];

        try {
            $conversation = Conversation::where('team_id', Auth::user()->currentTeam->id)->find($this->conversationId);
            if (! $conversation) {
                $this->suggestions = ["Conversation not found."];
                $this->isGenerating = false;
                return;
            }
            $messages = $conversation->messages()
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->reverse();

            $promptMessages = [
                ['role' => 'system', 'content' => 'You are a helpful customer support assistant for a business named '.Auth::user()->currentTeam->name.'. Provide 3 short, professional, and helpful reply suggestions based on the last messages. Output MUST be a JSON array of 3 strings.'],
            ];

            foreach ($messages as $msg) {
                $role = $msg->direction === 'inbound' ? 'user' : 'assistant';
                $promptMessages[] = ['role' => $role, 'content' => $msg->content];
            }

            $response = AIProviderManager::chat(Auth::user()->currentTeam, $promptMessages, [
                'max_tokens' => 300,
            ]);

            $content = $response['content'] ?? '';
            // Try to parse JSON from content
            if (preg_match('/\[.*\]/s', $content, $matches)) {
                $this->suggestions = json_decode($matches[0], true) ?: [];
            } else {
                $this->suggestions = [$content];
            }

        } catch (\Exception $e) {
            \Log::error('AI Assistant Error: '.$e->getMessage());
            $this->suggestions = ["Sorry, I couldn't generate suggestions right now."];
        }

        $this->isGenerating = false;
    }

    public function selectSuggestion($text)
    {
        $this->dispatch('aiSuggestionSelected', $text);
        $this->showAiModal = false;
    }

    public function selectSuggestionByIndex($index)
    {
        if (isset($this->suggestions[$index])) {
            $this->selectSuggestion($this->suggestions[$index]);
        }
    }

    public function render()
    {
        return view('livewire.chat.ai-assistant');
    }
}

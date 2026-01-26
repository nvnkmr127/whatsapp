<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;

class MessageWindow extends Component
{
    use WithFileUploads;

    public $conversationId;
    public $messageBody = '';
    public $newAttachment;
    public $selectedTemplateId;
    public $showEmojiPicker = false;
    public $conversation;

    // Template Modal State
    public $templateMediaUrl = '';
    public $showTemplateListModal = false;
    public $showTemplatePreviewModal = false;
    public $templateSearch = '';
    public $selectedTemplate;
    public $templateVariables = [];
    public $templatePreviewText = '';

    // Interactive Buttons State
    public $showInteractiveButtonsModal = false;
    public $buttonBody = '';
    public $interactiveButtons = []; // Array of titles

    public function getListeners()
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            $teamId = Auth::user()->currentTeam->id;
            return [
                "echo-private:teams.{$teamId},.MessageReceived" => 'handleIncomingMessage',
                "echo-private:teams.{$teamId},.MessageStatusUpdated" => 'handleStatusUpdate',
                "echo-presence:conversation.{$this->conversationId},.MessageReceived" => 'handleIncomingMessage',
                "echo-presence:conversation.{$this->conversationId},.MessageStatusUpdated" => 'handleStatusUpdate',
                "echo-presence:conversation.{$this->conversationId},client-typing" => 'handleClientTyping',
            ];
        }
        return [];
    }

    public function handleStatusUpdate($event)
    {
        // This will trigger a re-render or we can search and update the property
        $this->loadConversation();
    }

    public $chatMessages = []; // Dedicated property
    public $lastMessageId = null;

    public function mount($conversationId)
    {
        $this->conversationId = $conversationId;
        $this->loadConversation();
        // Initialize lastMessageId to avoid alert on load
        if (count($this->chatMessages) > 0) {
            $this->lastMessageId = $this->chatMessages->last()->id;
        }
    }

    public function loadConversation()
    {
        $this->conversation = Conversation::with(['contact'])->where('team_id', Auth::user()->currentTeam->id)->find($this->conversationId);

        if ($this->conversation) {
            // Load messages specifically and assign to public property
            $this->chatMessages = $this->conversation->messages()->orderBy('created_at', 'asc')->get();
        } else {
            $this->chatMessages = [];
        }

        if (count($this->chatMessages) > 0) {
            $latestId = $this->chatMessages->last()->id;

            // If we have a lastMessageId tracking and the new one is different -> New Message!
            if ($this->lastMessageId && $latestId > $this->lastMessageId) {
                $this->dispatch('play-sound');
                $this->dispatch('chat-scroll-bottom');
            }

            $this->lastMessageId = $latestId;
        }

        // Mark as read logic could go here
    }

    public function updatedNewAttachment()
    {
        $this->validate(['newAttachment' => 'max:16384']); // 16MB max
    }

    public function sendMessage()
    {
        Log::info("MessageWindow: sendMessage called", [
            'body' => $this->messageBody,
            'has_attachment' => $this->newAttachment ? true : false,
            'conversation_id' => $this->conversationId
        ]);
        $this->validate([
            'messageBody' => 'nullable|required_without:newAttachment|string',
            'newAttachment' => 'nullable|file|max:16384',
        ]);

        if (!$this->conversation)
            return;

        // 1. Pre-persist for immediate UI feedback (Optimistic Update)
        $msgData = [
            'team_id' => Auth::user()->currentTeam->id,
            'contact_id' => $this->conversation->contact_id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'queued',
        ];

        if ($this->newAttachment) {
            $path = $this->newAttachment->store('media', 'public');
            $url = asset(Storage::url($path));
            $type = $this->getMediaType($this->newAttachment->getMimeType());

            $msgData['type'] = $type;
            $msgData['content'] = $this->messageBody; // Caption
            $msgData['media_url'] = $path; // Store relative in DB
            $msgData['media_type'] = $type;
            $msgData['caption'] = $this->messageBody;
        } else {
            $msgData['type'] = 'text';
            $msgData['content'] = $this->messageBody;
            $url = $this->messageBody;
        }

        $message = \App\Models\Message::create($msgData);

        // 2. Dispatch Async Job
        \App\Jobs\SendMessageJob::dispatch(
            Auth::user()->currentTeam->id,
            $this->conversation->contact->phone_number,
            $msgData['type'],
            $url, // Use full URL or text body
            null, // templateName
            'en_US',
            $message->id
        );

        // 3. Update UI
        $this->reset(['messageBody', 'newAttachment']);
        $this->loadConversation();
        $this->dispatch('messageSent');
    }

    private function getMediaType($mime)
    {
        if (str_starts_with($mime, 'image/'))
            return 'image';
        if (str_starts_with($mime, 'video/'))
            return 'video';
        if (str_starts_with($mime, 'audio/'))
            return 'audio';
        return 'document';
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

    public function saveCallNote($messageId, $note)
    {
        $message = \App\Models\Message::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $messageId)
            ->first();

        if ($message) {
            $metadata = $message->metadata ?? [];
            $metadata['agent_note'] = $note;
            $metadata['note_saved_at'] = now()->timestamp;
            $message->update(['metadata' => $metadata]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Call note updated.'
            ]);
        }
    }

    public function handleIncomingMessage($event)
    {
        Log::info("MessageWindow: handleIncomingMessage received", ['event' => $event]);
        if ($this->conversation && $event['message']['conversation_id'] == $this->conversation->id) {
            $this->loadConversation();
            $this->dispatch('chat-scroll-bottom');
            $this->dispatch('play-sound');
        }
    }

    public function handleClientTyping($event)
    {
        // This will be handled by JS listener mainly, but we can have a fallback here if needed
        // For now, we rely on the JS listener in the blade file for visual feedback
    }

    public function toggleBot()
    {
        if (!$this->conversation || !$this->conversation->contact)
            return;

        $contact = $this->conversation->contact;
        $handoff = new \App\Services\BotHandoffService();

        if ($contact->is_bot_paused) {
            $handoff->resume($contact);
            $this->dispatch('bot-resumed');
        } else {
            $handoff->pause($contact, 'manual');
            $this->dispatch('bot-paused');
        }

        $this->loadConversation();
    }

    public function deleteAttachment()
    {
        $this->newAttachment = null;
    }

    public function getQuickRepliesProperty()
    {
        return \App\Models\CannedMessage::where('team_id', Auth::user()->currentTeam->id)
            ->latest()
            ->get()
            ->map(function ($msg) {
                return [
                    'code' => $msg->shortcut,
                    'text' => $msg->content
                ];
            });
    }

    public function getIsSessionOpenProperty()
    {
        if (!$this->conversation || !$this->conversation->contact) {
            return false;
        }
        $lastMsg = $this->conversation->contact->last_customer_message_at;
        return $lastMsg && $lastMsg->gt(now()->subHours(24));
    }

    public function getTemplatesProperty()
    {
        return \App\Models\WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)
            ->where('status', 'APPROVED')
            ->get();
    }

    public function openTemplateList()
    {
        $this->showTemplateListModal = true;
    }

    public function selectTemplate($templateId)
    {
        $this->selectedTemplate = \App\Models\WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)->find($templateId);

        if ($this->selectedTemplate) {
            $this->templateVariables = [];
            $this->parseTemplateVariables();
            $this->showTemplateListModal = false;
            $this->showTemplatePreviewModal = true;
        }
    }

    public function parseTemplateVariables()
    {
        if (!$this->selectedTemplate)
            return;

        // Find body component
        $components = $this->selectedTemplate->components ?? [];
        $bodyText = '';

        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                $bodyText = $component['text'] ?? '';
                break;
            }
        }

        $this->templatePreviewText = $bodyText;

        // Extract {{1}}, {{2}} etc
        preg_match_all('/{{(\d+)}}/', $bodyText, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $num) {
                // Initialize with empty or previous value
                $this->templateVariables[$num] = '';
            }
        }
    }

    public function getFilteredTemplatesProperty()
    {
        return \App\Models\WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)
            ->where('status', 'APPROVED')
            ->when($this->templateSearch, function ($query) {
                $query->where('name', 'like', '%' . $this->templateSearch . '%');
            })
            ->get();
    }

    public function getHasMediaHeaderProperty()
    {
        if (!$this->selectedTemplate)
            return false;
        $header = $this->getTemplateComponent('HEADER');
        return $header && in_array($header['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT']);
    }

    public function getTemplateComponent($type)
    {
        if (!$this->selectedTemplate)
            return null;
        return collect($this->selectedTemplate->components)->firstWhere('type', $type);
    }

    public function getLivePreviewTextProperty()
    {
        if (!$this->selectedTemplate)
            return '';

        $body = $this->getTemplateComponent('BODY');
        $text = $body['text'] ?? '';

        if (!empty($this->templateVariables)) {
            foreach ($this->templateVariables as $key => $value) {
                // Replace {{1}} with value or keep {{1}} if empty
                $replace = $value !== '' ? $value : "{{{$key}}}";
                $text = str_replace("{{{$key}}}", $replace, $text);
            }
        }

        return $text;
    }

    public function closeTemplateModals()
    {
        $this->showTemplateListModal = false;
        $this->showTemplatePreviewModal = false;
        $this->selectedTemplate = null;
        $this->templateVariables = [];
        $this->templateMediaUrl = '';
    }

    public function sendTemplateWithVariables()
    {
        if (!$this->selectedTemplate || !$this->conversation)
            return;

        try {
            ksort($this->templateVariables);
            $parameters = array_values($this->templateVariables);

            if ($this->templateMediaUrl) {
                array_unshift($parameters, $this->templateMediaUrl);
            }

            // 1. Pre-persist
            $richContent = "Template: {$this->selectedTemplate->name}";
            $bodyComp = collect($this->selectedTemplate->components)->firstWhere('type', 'BODY');
            if ($bodyComp) {
                $text = $bodyComp['text'] ?? '';
                foreach ($parameters as $index => $param) {
                    $search = '{{' . ($index + 1) . '}}';
                    $text = str_replace($search, $param, $text);
                }
                $richContent = $text;
            }

            $message = \App\Models\Message::create([
                'team_id' => Auth::user()->currentTeam->id,
                'contact_id' => $this->conversation->contact_id,
                'conversation_id' => $this->conversation->id,
                'type' => 'template',
                'direction' => 'outbound',
                'status' => 'queued',
                'content' => $richContent,
                'metadata' => [
                    'template_name' => $this->selectedTemplate->name,
                    'language' => $this->selectedTemplate->language ?? 'en_US',
                    'variables' => $parameters
                ],
            ]);

            // 2. Dispatch Job
            \App\Jobs\SendMessageJob::dispatch(
                Auth::user()->currentTeam->id,
                $this->conversation->contact->phone_number,
                'template',
                $parameters,
                $this->selectedTemplate->name,
                $this->selectedTemplate->language ?? 'en_US',
                $message->id
            );

            $this->dispatch('messageSent');
            $this->loadConversation();
            $this->closeTemplateModals();
            session()->flash('success', 'Template queued for sending.');

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // Deprecated or simplified direct send (kept for compatibility if needed, but UI will use modal now)
    public function sendTemplate($templateId)
    {
        // ... (Old logic, can be redirected or kept as fallback)
        $this->selectTemplate($templateId); // Redirect to modal flow
    }

    public function updatedSelectedTemplateId($value)
    {
        if ($value) {
            $this->sendTemplate($value);
            $this->selectedTemplateId = null; // Reset after sending
        }
    }

    // The original sendTemplate method is now modified to redirect to the modal flow.
    // The logic for sending templates with variables is now in sendTemplateWithVariables().
    // The original sendTemplate method's body is replaced by the instruction.
    // public function sendTemplate($templateId)
    // {
    //     \Log::info('sendTemplate called with ID: ' . $templateId);

    //     $template = \App\Models\WhatsappTemplate::find($templateId);

    //     if (!$template) {
    //         \Log::error('Template not found: ' . $templateId);
    //         session()->flash('error', 'Template not found');
    //         return;
    //     }

    //     if (!$this->conversation) {
    //         \Log::error('No conversation found');
    //         session()->flash('error', 'No conversation found');
    //         return;
    //     }

    //     \Log::info('Sending template: ' . $template->name . ' to ' . $this->conversation->contact->phone_number);

    //     $waService = new WhatsAppService();
    //     $waService->setTeam(Auth::user()->currentTeam);

    //     try {
    //         // For now, sending with no parameters. Future: Support variables.
    //         $response = $waService->sendTemplate(
    //             $this->conversation->contact->phone_number,
    //             $template->name,
    //             $template->language ?? 'en_US'
    //         );

    //         \Log::info('Template response: ', $response);

    //         if ($response['success'] ?? false) {
    //             // Reset and maybe refresh conversation
    //             $this->dispatch('messageSent'); // Optional
    //             $this->loadConversation();
    //             session()->flash('success', 'Template sent successfully!');
    //         } else {
    //             $errorMsg = $response['error']['message'] ?? 'Unknown error';
    //             \Log::error('Template failed: ' . $errorMsg);
    //             session()->flash('error', 'Template failed: ' . $errorMsg);
    //         }
    //     } catch (\Exception $e) {
    //         \Log::error('Template exception: ' . $e->getMessage());
    //         session()->flash('error', $e->getMessage());
    //     }
    // }

    public function openInteractiveButtonsModal()
    {
        $this->buttonBody = '';
        $this->interactiveButtons = ['']; // Start with one empty button
        $this->showInteractiveButtonsModal = true;
    }

    public function addInteractiveButton()
    {
        if (count($this->interactiveButtons) < 3) {
            $this->interactiveButtons[] = '';
        }
    }

    public function removeInteractiveButton($index)
    {
        unset($this->interactiveButtons[$index]);
        $this->interactiveButtons = array_values($this->interactiveButtons);
    }

    public function sendInteractiveButtons()
    {
        $this->validate([
            'buttonBody' => 'required|string',
            'interactiveButtons' => 'required|array|min:1|max:3',
            'interactiveButtons.*' => 'required|string|max:20',
        ]);

        if (!$this->conversation)
            return;

        try {
            // Prepare buttons
            $buttons = [];
            foreach ($this->interactiveButtons as $title) {
                $id = 'btn_' . \Illuminate\Support\Str::slug($title);
                $buttons[$id] = $title;
            }

            // 1. Pre-persist
            $message = \App\Models\Message::create([
                'team_id' => Auth::user()->currentTeam->id,
                'contact_id' => $this->conversation->contact_id,
                'conversation_id' => $this->conversation->id,
                'type' => 'interactive',
                'direction' => 'outbound',
                'status' => 'queued',
                'content' => $this->buttonBody,
                'metadata' => ['buttons' => $buttons],
            ]);

            // 2. Dispatch (Currently SendMessageJob doesn't support 'interactive' natively, let's fix that or use service)
            // Fix: Add 'interactive' support to SendMessageJob or call sync for now (buttons are rare)
            // For audit compliance, EVERYTHING outbound should be async.
            // I'll update SendMessageJob to support 'interactive' as well.

            \App\Jobs\SendMessageJob::dispatch(
                Auth::user()->currentTeam->id,
                $this->conversation->contact->phone_number,
                'interactive',
                $this->buttonBody,
                null,
                'en_US',
                $message->id
            );

            $this->dispatch('messageSent');
            $this->loadConversation();
            $this->showInteractiveButtonsModal = false;
            $this->reset(['buttonBody', 'interactiveButtons']);
            session()->flash('success', 'Buttons queued successfully!');

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * API for Alpine Store to fetch messages as JSON
     */
    #[\Livewire\Attributes\Renderless]
    public function loadMessagesJson($offset = 0, $limit = 50)
    {
        if (!$this->conversation)
            return [];

        return $this->conversation->messages()
            ->with(['attributedCampaign'])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'direction' => $msg->direction,
                    'content' => $msg->content,
                    'type' => $msg->type,
                    'status' => $msg->status,
                    'created_at' => $msg->created_at->timestamp, // Unix for easier JS sort
                    'pretty_time' => $msg->created_at->format('H:i'),
                    'media_url' => $msg->media_url ? (\Illuminate\Support\Facades\Storage::url($msg->media_url)) : null,
                    'media_type' => $msg->media_type,
                    'caption' => $msg->caption,
                    'error_message' => $msg->error_message, // For failed status
                    'is_outbound' => $msg->direction === 'outbound',
                    'attributed_campaign_name' => $msg->attributedCampaign?->name,
                    'metadata' => $msg->metadata,
                ];
            })
            ->reverse() // Return chronological for the list
            ->values()
            ->toArray();
    }

    /**
     * API for Alpine Store to send text messages and get ID
     */
    #[\Livewire\Attributes\Renderless]
    public function sendMessageJson($body, $tempId)
    {
        if (empty($body) || !$this->conversation) {
            return ['status' => 'error', 'message' => 'Invalid session'];
        }

        // Multi-Agent Safety: Double Commit Guard
        // 1. Check if another agent replied in the very last second (Race Condition)
        if ($this->conversation->last_message_at && $this->conversation->last_message_at->diffInSeconds(now()) < 2) {
            // Assuming last message was outbound agent 
            // We can query the actual last message to be sure it wasn't the customer
            $lastMsg = $this->conversation->messages()->latest()->first();
            if ($lastMsg && $lastMsg->direction === 'outbound' && $lastMsg->created_at->diffInSeconds(now()) < 2) {
                return ['status' => 'error', 'message' => 'Collision Detected: Another agent just sent a message.'];
            }
        }

        // 2. Check Lock Ownership (Optional strictness)
        $lockKey = "conversation_lock:{$this->conversation->id}";
        // Replace Redis with Cache for shared hosting compatibility
        $lockOwner = \Illuminate\Support\Facades\Cache::get($lockKey);
        if ($lockOwner && (int) $lockOwner !== Auth::id()) {
            // We allow replying if lock is expired (empty) but if someone else holds it, we reject.
            // However, UI handles input fix usually. This is a safety check.
            return ['status' => 'error', 'message' => 'This chat is locked by another agent.'];
        }

        $msgData = [
            'team_id' => Auth::user()->currentTeam->id,
            'contact_id' => $this->conversation->contact_id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'queued',
            'type' => 'text',
            'content' => $body
        ];

        $message = \App\Models\Message::create($msgData);

        // Update conversation last_message_at immediately for the guard to work for others
        $this->conversation->update(['last_message_at' => now()]);

        // Release lock immediately after sending (optional, or let blur handle it)
        // \App\Services\ConversationService::releaseLock($this->conversation->id, Auth::id());

        \App\Jobs\SendMessageJob::dispatch(
            Auth::user()->currentTeam->id,
            $this->conversation->contact->phone_number,
            'text',
            $body,
            null,
            'en_US',
            $message->id
        );

        return [
            'id' => $message->id,
            'temp_id' => $tempId,
            'status' => 'queued',
            'created_at' => $message->created_at->timestamp,
            'pretty_time' => $message->created_at->format('H:i'),
        ];
    }

    public function render()
    {
        return view('livewire.chat.message-window', [
            'isSessionOpen' => $this->isSessionOpen,
            'templates' => $this->isSessionOpen ? [] : $this->templates,
            'quickReplies' => $this->quickReplies
        ]);
    }
}

<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
// use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class MessageWindow extends Component
{
    use WithFileUploads;

    public $conversationId;
    public $messageBody = '';
    public $newAttachment;
    public $selectedTemplateId;
    public $showEmojiPicker = false;
    public $conversation;
    public $activeAgents = [];
    public $availableCategories = [];

    protected $listeners = [
        'refresh-tags' => 'loadConversation',
        'templateSelected' => 'handleTemplateSelected',
        'mediaUploaded' => 'handleMediaUploaded',
        'mediaDeleted' => 'handleMediaDeleted',
        'aiSuggestionSelected' => 'handleAiSuggestionSelected',
    ];

    // Template Modal State (Removed, now handled by sub-components)
    // public $templateMediaUrl = '';
    // public $showTemplateListModal = false;
    // public $showTemplatePreviewModal = false;
    // public $templateSearch = '';
    // public $selectedTemplate;
    // public $templateVariables = [];
    // public $templatePreviewText = '';

    // New properties for transfer modal and refactored media/message
    public $activeAgents = [];
    public $activeAgentIds = [];
    public $showTransferModal = false;
    public $transferSearch = '';
    public $activeAgentsFull = [];

    // Media and Templates (Now handled by sub-components)
    public $newAttachmentData = null;
    public $msgBody = '';
    public $isNoteMode = false;

    // Interactive Buttons State
    public $showInteractiveButtonsModal = false;
    public $buttonBody = '';
    public $interactiveButtons = []; // Array of titles

    // Lightbox State
    public $lightboxOpen = false;
    public $lightboxImage = '';

    public function getListeners()
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            $teamId = Auth::user()->currentTeam->id;
            return [
                "echo-private:teams.{$teamId},.MessageReceived" => 'handleIncomingMessage',
                "echo-private:teams.{$teamId},.MessageStatusUpdated" => 'handleStatusUpdate',
                "echo-presence:conversation.{$this->conversationId},.MessageReceived" => 'handleIncomingMessage',
                "echo-presence:conversation.{$this->conversationId},.MessageStatusUpdated" => 'handleStatusUpdate',
                'templateSelected' => 'handleTemplateSelected',
                'mediaUploaded' => 'handleMediaUploaded',
                'mediaDeleted' => 'handleMediaDeleted',
                'aiSuggestionSelected' => 'handleAiSuggestionSelected',
                "echo-presence:conversation.{$this->conversationId},client-typing" => 'handleClientTyping',
                "echo-presence:conversation.{$this->conversationId},here" => 'handlePresence',
                "echo-presence:conversation.{$this->conversationId},joining" => 'handlePresenceJoin',
                "echo-presence:conversation.{$this->conversationId},leaving" => 'handlePresenceLeave',
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

        $this->availableCategories = \App\Models\Category::where('team_id', Auth::user()->currentTeam->id)
            ->whereIn('target_module', ['chat', 'all'])
            ->where('is_active', true)
            ->get();

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

            if (count($this->chatMessages) > 0) {
                $latestId = $this->chatMessages->last()->id;

                // If we have a lastMessageId tracking and the new one is different -> New Message!
                if ($this->lastMessageId && $latestId > $this->lastMessageId) {
                    $this->dispatch('play-sound');
                    $this->dispatch('chat-scroll-bottom');
                }

                $this->lastMessageId = $latestId;
            }

            // Mark as read locally and sync to WhatsApp if enabled
            $unreadMessages = $this->conversation->messages()
                ->where('direction', 'inbound')
                ->whereNull('read_at')
                ->get();

            if ($unreadMessages->isNotEmpty()) {
                $this->conversation->messages()
                    ->whereIn('id', $unreadMessages->pluck('id'))
                    ->update(['read_at' => now()]);

                if (Auth::user()->currentTeam->read_receipts_enabled) {
                    $wa = new \App\Services\WhatsAppService(Auth::user()->currentTeam);
                    foreach ($unreadMessages as $msg) {
                        if ($msg->whatsapp_message_id) {
                            try {
                                $wa->markRead($msg->whatsapp_message_id);
                            } catch (\Exception $e) {
                                Log::warning("Failed to mark message {$msg->whatsapp_message_id} as read on WhatsApp: " . $e->getMessage());
                            }
                        }
                    }
                }
                $this->dispatch('chat-messages-read');
            }
        } else {
            $this->chatMessages = [];
        }
    }

    // public function updatedNewAttachment() // Removed
    // {
    //     $this->validate(['newAttachment' => 'max:16384']); // 16MB max
    // }

    public function handleTemplateSelected($payload)
    {
        if (!$this->conversation) return;

        $wa = new \App\Services\WhatsAppService(Auth::user()->currentTeam);
        
        // Extract variables (header + body)
        $variables = $payload['variables'];
        $headerParams = [];
        if (isset($variables['header_media_url'])) {
            $headerParams = [$variables['header_media_url']];
            unset($variables['header_media_url']);
        }
        $bodyParams = array_values($variables);

        try {
            $wa->sendTemplate(
                $this->conversation->contact->phone_number,
                $payload['template_name'],
                $payload['language'],
                $bodyParams,
                $headerParams
            );
            $this->loadConversation();
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function handleMediaUploaded($payload)
    {
        $this->newAttachmentData = $payload;
    }

    public function handleMediaDeleted()
    {
        $this->newAttachmentData = null;
    }

    public function handleAiSuggestionSelected($text)
    {
        $this->msgBody = $text;
    }

    public function sendMessage()
    {
        Log::info("MessageWindow: sendMessage called", [
            'body' => $this->msgBody,
            'has_attachment' => $this->newAttachmentData ? true : false,
            'conversation_id' => $this->conversationId
        ]);
        $this->validate([
            'msgBody' => 'nullable|required_without:newAttachmentData|string',
            'newAttachmentData' => 'nullable|array', // Validate as array from sub-component
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

        $attachments = null;
        if ($this->newAttachmentData) {
            // Since the real upload happened in the sub-component,
            // the sub-component should have stored it properly if we wanted to use it here.
            // Actually, MediaUpload component currently only sends back the temp url.
            // We need the ACTUAL file object or a path.
            // Let's assume for now, the MediaUpload component SHOULD have used FileUpload trait
            // and we share it? No, components don't share file objects easily.
            // FIX: We'll make MessageWindow handle the ACTUAL send if it gets a temp path.
            // But wait, it's easier to let the sub-component handle the upload and we just get the path.
            $path = $this->newAttachmentData['path']; // Assuming path is provided by sub-component
            $url = asset(Storage::url($path));
            $type = $this->getMediaType($this->newAttachmentData['mime_type']);

            $msgData['type'] = $type;
            $msgData['content'] = $this->msgBody; // Caption
            $msgData['media_url'] = $path; // Store relative in DB
            $msgData['media_type'] = $type;
            $msgData['caption'] = $this->msgBody;
        } else {
            $msgData['type'] = 'text';
            $msgData['content'] = $this->msgBody;
            $url = $this->msgBody;
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
        $this->msgBody = '';
        $this->newAttachmentData = null;
        $this->dispatch('clearMediaUpload'); // New signal to reset sub-component
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

    public function saveInternalNote()
    {
        $this->validate([
            'msgBody' => 'required|string',
        ]);

        if (!$this->conversation)
            return;

        $message = \App\Models\Message::create([
            'team_id' => Auth::user()->currentTeam->id,
            'contact_id' => $this->conversation->contact_id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'sent',
            'type' => 'internal_note',
            'content' => $this->msgBody,
            'metadata' => ['agent_id' => Auth::id(), 'agent_name' => Auth::user()->name],
        ]);

        $this->reset('msgBody');
        $this->loadConversation();
        $this->dispatch('messageSent');
    }

    public function transferConversation($agentId)
    {
        if (!$this->conversation)
            return;

        $agent = Auth::user()->currentTeam->users()->find($agentId);
        if (!$agent)
            return;

        $this->conversation->update([
            'assigned_to' => $agentId,
        ]);

        // Log transfer as internal note
        \App\Models\Message::create([
            'team_id' => Auth::user()->currentTeam->id,
            'contact_id' => $this->conversation->contact_id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'sent',
            'type' => 'internal_note',
            'content' => "Conversation transferred to {$agent->name}.",
            'metadata' => ['type' => 'transfer', 'transferred_by' => Auth::user()->name],
        ]);

        $agent->notify(new \App\Notifications\ConversationAssignedNotification($this->conversation, Auth::user()));
        \App\Events\ConversationAssigned::dispatch($this->conversation, $agent, Auth::user());

        $this->loadConversation();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Conversation transferred to {$agent->name}"
        ]);
    }



    public function sendVoiceNote($audioFile)
    {
        // This is called after Livewire finishes uploading the blob
        $file = $audioFile ?: $this->newAttachmentData; // Changed from newAttachment
        if (!$this->conversation || !$file)
            return;

        $path = $file->store('voice-notes', 'public');

        $message = \App\Models\Message::create([
            'team_id' => Auth::user()->currentTeam->id,
            'contact_id' => $this->conversation->contact_id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'queued',
            'type' => 'audio',
            'media_url' => $path,
            'media_type' => 'audio/ogg',
        ]);

        \App\Jobs\SendMessageJob::dispatch(
            Auth::user()->currentTeam->id,
            $this->conversation->contact->phone_number,
            'audio',
            asset(Storage::url($path)),
            null,
            'en_US',
            $message->id
        );

        if ($this->newAttachmentData) { // Changed from newAttachment
            $this->reset('newAttachmentData'); // Changed from newAttachment
        }
        $this->loadConversation();
        $this->dispatch('messageSent');
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

    public function reopenConversation()
    {
        if ($this->conversation) {
            $this->conversation->update([
                'status' => 'open',
                'closed_at' => null,
                'close_reason' => null
            ]);

            // Create internal note
            \App\Models\Message::create([
                'team_id' => Auth::user()->currentTeam->id,
                'contact_id' => $this->conversation->contact_id,
                'conversation_id' => $this->conversation->id,
                'direction' => 'outbound',
                'status' => 'sent',
                'type' => 'internal_note',
                'content' => "Conversation reopened by " . Auth::user()->name,
                'metadata' => ['type' => 'reopen', 'reopened_by' => Auth::user()->name],
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Conversation reopened successfully'
            ]);
            $this->loadConversation();
        }
    }

    public function markAsSpam()
    {
        if ($this->conversation && $this->conversation->contact) {
            $metadata = $this->conversation->metadata ?? [];
            $metadata['marked_as_spam'] = true;
            $metadata['spam_marked_at'] = now()->toISOString();
            $metadata['spam_marked_by'] = Auth::user()->name;

            $this->conversation->update([
                'metadata' => $metadata,
                'status' => 'closed',
                'close_reason' => 'spam'
            ]);

            // Create internal note
            \App\Models\Message::create([
                'team_id' => Auth::user()->currentTeam->id,
                'contact_id' => $this->conversation->contact_id,
                'conversation_id' => $this->conversation->id,
                'direction' => 'outbound',
                'status' => 'sent',
                'type' => 'internal_note',
                'content' => "Conversation marked as spam by " . Auth::user()->name,
                'metadata' => ['type' => 'spam', 'marked_by' => Auth::user()->name],
            ]);

            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Conversation marked as spam'
            ]);
            $this->loadConversation();
        }
    }

    public function blockContact()
    {
        if ($this->conversation && $this->conversation->contact) {
            $this->conversation->contact->update([
                'is_blocked' => true,
                'blocked_at' => now(),
                'blocked_by' => Auth::id()
            ]);

            $this->conversation->update([
                'status' => 'closed',
                'close_reason' => 'contact_blocked'
            ]);

            // Create internal note
            \App\Models\Message::create([
                'team_id' => Auth::user()->currentTeam->id,
                'contact_id' => $this->conversation->contact_id,
                'conversation_id' => $this->conversation->id,
                'direction' => 'outbound',
                'status' => 'sent',
                'type' => 'internal_note',
                'content' => "Contact blocked by " . Auth::user()->name,
                'metadata' => ['type' => 'block', 'blocked_by' => Auth::user()->name],
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Contact has been blocked'
            ]);
            $this->loadConversation();
        }
    }

    public function exportConversation()
    {
        if (!$this->conversation) return;

        $messages = $this->conversation->messages()->orderBy('created_at', 'asc')->get();
        $filename = "conversation_{$this->conversation->id}_" . now()->format('Y-m-d_H-i-s') . ".csv";

        return response()->streamDownload(function () use ($messages) {
            $handle = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 support
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Headers
            fputcsv($handle, ['Timestamp', 'Sender', 'Type', 'Content', 'Status']);

            foreach ($messages as $msg) {
                $sender = $msg->direction === 'inbound'
                    ? ($this->conversation->contact->name ?? $this->conversation->contact->phone_number)
                    : (isset($msg->metadata['agent_name']) ? "Agent ({$msg->metadata['agent_name']})" : "System/Agent");

                $content = $msg->content;
                if ($msg->type !== 'text') {
                    $content = "[{$msg->type}] " . ($msg->caption ?? "Media asset: " . ($msg->media_url ?? 'N/A'));
                }

                fputcsv($handle, [
                    $msg->created_at->format('Y-m-d H:i:s'),
                    $sender,
                    ucfirst($msg->type),
                    $content,
                    ucfirst($msg->status)
                ]);
            }

            fclose($handle);
        }, $filename);
    }

    public function saveCallNote($messageId, $note)
    {
        $message = \App\Models\Message::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $messageId)
            ->first();

        if ($message) {
            $metadata = $message->metadata;
            if (!is_array($metadata)) {
                $metadata = [];
            }
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

    public function handlePresence($users)
    {
        $this->activeAgents = $users;
    }

    public function handlePresenceJoin($user)
    {
        if (!collect($this->activeAgents)->contains('id', $user['id'])) {
            $this->activeAgents[] = $user;
        }
    }

    public function handlePresenceLeave($user)
    {
        $this->activeAgents = collect($this->activeAgents)
            ->filter(fn($u) => $u['id'] != $user['id'])
            ->values()
            ->all();
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
        $this->newAttachmentData = null; // Changed from newAttachment
        $this->dispatch('clearMediaUpload'); // Signal to sub-component
    }

    public function toggleCategory($categoryId)
    {
        if (!$this->conversation)
            return;

        $metadata = $this->conversation->metadata;
        if (!is_array($metadata)) {
            $metadata = [];
        }
        $tags = $metadata['tags'] ?? [];

        if (in_array($categoryId, $tags)) {
            $tags = array_values(array_filter($tags, fn($id) => $id != $categoryId));
        } else {
            $tags[] = $categoryId;
        }

        $metadata['tags'] = $tags;
        $this->conversation->update(['metadata' => $metadata]);
        $this->dispatch('refresh-tags');
        $this->loadConversation();
    }

    public function addReaction($messageId, $emoji)
    {
        if (!Auth::check() || !Auth::user()->currentTeam) {
            return;
        }

        $message = \App\Models\Message::where('team_id', Auth::user()->currentTeam->id)
            ->find($messageId);
        if (!$message)
            return;

        $metadata = $message->metadata;
        if (!is_array($metadata)) {
            $metadata = [];
        }

        $reactions = $metadata['reactions'] ?? [];
        if (!is_array($reactions)) {
            $reactions = [];
        }

        // Toggle or Add
        $myId = Auth::id();
        if (($reactions[$myId] ?? null) === $emoji) {
            unset($reactions[$myId]);
        } else {
            $reactions[$myId] = $emoji;
        }

        $metadata['reactions'] = $reactions;
        $message->update(['metadata' => $metadata]);

        // Broadcast update
        \App\Events\MessageStatusUpdated::dispatch($message);
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

    public function getAgentsProperty()
    {
        return Auth::user()->currentTeam->users()
            ->where('users.id', '!=', Auth::id())
            ->get();
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

    // public function openTemplateList() // Removed
    // {
    //     $this->showInteractiveButtonsModal = false;
    //     $this->showTemplateListModal = true;
    // }

    // public function selectTemplate($templateId) // Removed
    // {
    //     $this->selectedTemplate = \App\Models\WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)->find($templateId);

    //     if ($this->selectedTemplate) {
    //         $this->templateVariables = [];
    //         $this->parseTemplateVariables();
    //         $this->showTemplateListModal = false;
    //         $this->showTemplatePreviewModal = true;
    //     }
    // }

    // public function parseTemplateVariables() // Removed
    // {
    //     if (!$this->selectedTemplate)
    //         return;

    //     // Find body component
    //     $components = $this->selectedTemplate->components ?? [];
    //     $bodyText = '';

    //     foreach ($components as $component) {
    //         if (($component['type'] ?? '') === 'BODY') {
    //             $bodyText = $component['text'] ?? '';
    //             break;
    //         }
    //     }

    //     $this->templatePreviewText = $bodyText;

    //     // Extract {{1}}, {{2}} etc
    //     preg_match_all('/{{(\d+)}}/', $bodyText, $matches);

    //     if (!empty($matches[1])) {
    //         foreach ($matches[1] as $num) {
    //             // Initialize with empty or previous value
    //             $this->templateVariables[$num] = '';
    //         }
    //     }
    // }

    /**
     * Removed in favor of TemplatePicker component
     */
    /*
    public function getFilteredTemplatesProperty()
    {
        ...
    }
    */
    // public function getHasMediaHeaderProperty() // Removed
    // {
    //     if (!$this->selectedTemplate)
    //         return false;
    //     $header = $this->getTemplateComponent('HEADER');
    //     return $header && in_array($header['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT']);
    // }

    // public function getTemplateComponent($type) // Removed
    // {
    //     if (!$this->selectedTemplate)
    //         return null;
    //     return collect($this->selectedTemplate->components)->firstWhere('type', $type);
    // }

    // public function getLivePreviewTextProperty() // Removed
    // {
    //     if (!$this->selectedTemplate)
    //         return '';

    //     $body = $this->getTemplateComponent('BODY');
    //     $text = $body['text'] ?? '';

    //     // 1. Escape HTML
    //     $text = e($text);

    //     // 2. Wrap variables in spans BEFORE markdown (to avoid markdown inside variables)
    //     $text = preg_replace('/{{(\d+)}}/', '<span class="bg-slate-200 dark:bg-slate-600 px-1 rounded mx-0.5 shadow-sm border border-slate-300 dark:border-slate-500 font-mono text-[10px]">{{$1}}</span>', $text);

    //     // 3. Replace variables with actual values (preserving spans)
    //     if (!empty($this->templateVariables)) {
    //         foreach ($this->templateVariables as $key => $value) {
    //             if ($value !== '') {
    //                 $search = '<span class="bg-slate-200 dark:bg-slate-600 px-1 rounded mx-0.5 shadow-sm border border-slate-300 dark:border-slate-500 font-mono text-[10px]">{{' . $key . '}}</span>';
    //                 $replacement = '<span class="bg-wa-teal/10 text-wa-teal px-1.5 py-0.5 rounded-md mx-0.5 shadow-sm border border-wa-teal/20 font-bold text-[11px]">' . e($value) . '</span>';
    //                 $text = str_replace($search, $replacement, $text);
    //             }
    //         }
    //     }

    //     // 4. WhatsApp Markdown
    //     $text = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $text);
    //     $text = preg_replace('/_(.*?)_/', '<em>$1</em>', $text);
    //     $text = preg_replace('/~(.*?)~/', '<del>$1</del>', $text);

    //     return $text;
    // }

    public function getPreviewButtonBodyProperty()
    {
        $text = $this->buttonBody ?: 'Message text...';

        // 1. Escape HTML
        $text = e($text);

        // 2. WhatsApp Markdown
        $text = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/_(.*?)_/', '<em>$1</em>', $text);
        $text = preg_replace('/~(.*?)~/', '<del>$1</del>', $text);

        return $text;
    }

            return;

        try {
            ksort($this->templateVariables);
            $bodyParams = array_values($this->templateVariables);
            $headerParams = $this->templateMediaUrl ? [$this->templateMediaUrl] : [];
            $footerParams = [];

            // 1. Pre-persist for UI
            $richContent = "Template: {$this->selectedTemplate->name}";
            $bodyComp = collect($this->selectedTemplate->components)->firstWhere('type', 'BODY');
            if ($bodyComp) {
                $text = $bodyComp['text'] ?? '';
                foreach ($bodyParams as $index => $param) {
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
                    'variables' => $bodyParams,
                    'header_variables' => $headerParams,
                    'footer_variables' => $footerParams,
                ],
            ]);

            // 2. Dispatch Job with segregated parameters
            \App\Jobs\SendMessageJob::dispatch(
                Auth::user()->currentTeam->id,
                $this->conversation->contact->phone_number,
                'template',
                $bodyParams,
                $this->selectedTemplate->name,
                $this->selectedTemplate->language ?? 'en_US',
                $message->id,
                null, // traceId
                $headerParams,
                $footerParams
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

    public function openInteractiveButtonsModal()
    {
        $this->closeTemplateModals();
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

            // 2. Dispatch


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
        if (!$this->conversation) {
            if ($this->conversationId) {
                // Try to reload conversation if missing (Hydration issue safety)
                $this->loadConversation();
            }

            if (!$this->conversation) {
                \Log::error('MessageWindow: loadMessagesJson failed - No conversation found', ['id' => $this->conversationId]);
                return [];
            }
        }

        $messages = $this->conversation->messages()
            ->with(['attributedCampaign'])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        \Log::info("MessageWindow: loadMessagesJson", [
            'conversation_id' => $this->conversationId,
            'count' => $messages->count(),
            'offset' => $offset
        ]);

        return $messages
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'direction' => $msg->direction,
                    'content' => $msg->content,
                    'type' => $msg->type,
                    'status' => $msg->status,
                    'created_at' => $msg->created_at->timestamp, // Unix for easier JS sort
                    'pretty_time' => $msg->created_at->format('H:i'),
                    'media_url' => $msg->media_url ? (str_starts_with($msg->media_url, 'http') ? $msg->media_url : \Illuminate\Support\Facades\Storage::url($msg->media_url)) : null,
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

        // Removed strict collision check to allow rapid messaging by same agent.
        // Was causing false positives when sending multiple messages quickly.


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
            'quickReplies' => $this->quickReplies,
            'agents' => $this->agents
        ]);
    }
}

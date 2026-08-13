<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
// use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class MessageWindow extends Component
{
    use WithFileUploads;

    public $conversationId;

    public $newAttachment;

    public $voiceNote;

    public $uploadError = null;

    public $selectedTemplateId;

    public $conversation;

    public $availableCategories = [];

    // Legacy template preview modal state (still referenced by message-window view)
    public $templateMediaUrl = '';

    public $showTemplatePreviewModal = false;

    public $selectedTemplate = null;

    public $templateVariables = [];

    protected $listeners = [
        'refresh-tags' => 'loadConversation',
        'contact-updated' => 'loadConversation',
        'templateSelected' => 'handleTemplateSelected',
        'aiSuggestionSelected' => 'handleAiSuggestionSelected',
    ];

    public $showTransferModal = false;

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
    
    // Reply State
    public $replyToMessageId = null;

    // Forward State
    public $showForwardModal = false;
    public $forwardingMessageId = null;
    public $forwardSelectedConversations = [];
    public $forwardRecentConversations = [];

    public $lastMessageId = null;

    /**
     * First page of bubbles, embedded straight into the initial render so opening a
     * chat costs one roundtrip instead of render + a follow-up loadMessagesJson().
     * Deliberately NOT a public property — it would ride along in every payload.
     */
    protected $initialMessages = null;

    public function mount($conversationId)
    {
        $this->conversationId = $conversationId;
        $this->loadConversation();
        $this->initialMessages = $this->loadMessagesJson(0, 50);

        $this->availableCategories = \App\Models\Category::where('team_id', Auth::user()->currentTeam->id)
            ->whereIn('target_module', ['chat', 'all'])
            ->where('is_active', true)
            ->get();
    }

    public function loadConversation()
    {
        $this->conversation = Conversation::with(['contact'])->where('team_id', Auth::user()->currentTeam->id)->find($this->conversationId);

        if (! $this->conversation) {
            return;
        }

        // The bubbles come from the Alpine store via loadMessagesJson(), so only the
        // newest id is needed here. Hydrating 50 Message models on every action
        // (send, react, tag, transfer…) was pure waste.
        $latestId = $this->conversation->messages()->max('id');

        if ($latestId) {
            if ($this->lastMessageId && $latestId > $this->lastMessageId) {
                $this->dispatch('play-sound');
                $this->dispatch('chat-scroll-bottom');
            }
            $this->lastMessageId = $latestId;
        }

        $this->markAsRead();
    }

    /** Mark inbound messages read locally and sync the receipts to WhatsApp async. */
    protected function markAsRead(): void
    {
        $unread = $this->conversation->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at');

        // Must be read before the UPDATE clears the rows from the filter.
        $waIds = Auth::user()->currentTeam->read_receipts_enabled
            ? (clone $unread)->whereNotNull('whatsapp_message_id')->pluck('whatsapp_message_id')->all()
            : [];

        if ($unread->update(['read_at' => now()]) === 0) {
            return;
        }

        if (! empty($waIds)) {
            \App\Jobs\MarkAsReadJob::dispatch(Auth::user()->currentTeam->id, $waIds);
        }

        $this->dispatch('chat-messages-read');
    }

    public function handleTemplateSelected($payload)
    {
        if (! $this->conversation) {
            return;
        }

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

    /**
     * Fired by the composer file input AND by drag-and-drop (both upload to
     * `newAttachment`). The Livewire temporary URL expires and is not reachable
     * by Meta, so the media MUST be persisted before sending.
     */
    public function updatedNewAttachment()
    {
        $this->uploadError = null;
        $this->newAttachmentData = null;

        if (! $this->newAttachment) {
            return;
        }

        try {
            $this->validate(['newAttachment' => 'max:16384']); // 16MB

            $this->newAttachmentData = [
                'name' => $this->newAttachment->getClientOriginalName(),
                'mime_type' => $this->newAttachment->getMimeType(),
                'size' => $this->newAttachment->getSize(),
                // Same disk full_media_url resolves against, or SendMessageJob
                // hands Meta a URL that 404s.
                'path' => $this->newAttachment->store('chat-media', config('filesystems.default')),
            ];
        } catch (\Exception $e) {
            $this->uploadError = 'File upload failed: '.$e->getMessage();
            $this->reset('newAttachment');
        }
    }

    public function handleAiSuggestionSelected($text)
    {
        $this->msgBody = $text;
    }

    /**
     * Send the picked media attachment (image/video/audio/document).
     * The file was persisted by updatedNewAttachment(), which left
     * its stored path in newAttachmentData.
     */
    public function sendMessage()
    {
        if (! $this->conversation || empty($this->newAttachmentData['path'])) {
            return;
        }

        $mime = $this->newAttachmentData['mime_type'] ?? 'application/octet-stream';
        $type = $this->getMediaType($mime);
        $caption = $this->msgBody ?: null;

        $message = \App\Models\Message::create([
            'team_id' => Auth::user()->currentTeam->id,
            'contact_id' => $this->conversation->contact_id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'queued',
            'type' => $type,
            'content' => $caption,
            'caption' => $caption,
            'media_url' => $this->newAttachmentData['path'] ?? null,
            'media_type' => $mime,
            'metadata' => ['agent_id' => Auth::id(), 'agent_name' => Auth::user()->name],
            'reply_to_message_id' => $this->replyToMessageId,
        ]);

        // Message-based dispatch resolves the public URL via $message->full_media_url.
        \App\Jobs\SendMessageJob::dispatch($message);

        if ($this->conversation?->contact) {
            app(\App\Services\BotHandoffService::class)->handleAgentActivity($this->conversation->contact);
        }

        $this->msgBody = '';
        $this->replyToMessageId = null;
        $this->newAttachmentData = null;
        // The Message row owns the file now — clear the picker without deleting it.
        $this->reset('newAttachment');
        $this->loadConversation();
        $this->dispatch('messageSent');
    }

    private function getMediaType(string $mime): string
    {
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    public function closeTemplateModals()
    {
        $this->showTemplatePreviewModal = false;
        $this->selectedTemplate = null;
        $this->templateVariables = [];
        $this->templateMediaUrl = '';
    }

    public function selectTemplate($templateId)
    {
        $template = \App\Models\WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)
            ->find($templateId);

        if (! $template) {
            return;
        }

        $this->selectedTemplateId = $template->id;
        $this->selectedTemplate = $template;
        $this->templateVariables = [];
        $this->parseTemplateVariables();
        $this->showTemplatePreviewModal = true;
    }

    public function sendTemplateWithVariables()
    {
        if (! $this->selectedTemplate) {
            return;
        }

        $this->handleTemplateSelected([
            'template_name' => $this->selectedTemplate->name,
            'language' => $this->selectedTemplate->language ?? 'en_US',
            'variables' => $this->templateVariables,
        ]);

        $this->closeTemplateModals();
    }

    public function parseTemplateVariables()
    {
        if (! $this->selectedTemplate) {
            return;
        }

        $components = $this->selectedTemplate->components ?? [];
        if (is_string($components)) {
            $components = json_decode($components, true) ?: [];
        }

        $bodyText = '';
        $header = null;

        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                $bodyText = $component['text'] ?? '';
            }
            if (($component['type'] ?? '') === 'HEADER') {
                $header = $component;
            }
        }

        preg_match_all('/{{(\d+)}}/', $bodyText, $matches);
        if (! empty($matches[1])) {
            foreach ($matches[1] as $num) {
                $this->templateVariables[(string) $num] = $this->templateVariables[(string) $num] ?? '';
            }
        }

        if (($header['format'] ?? '') === 'IMAGE' || ($header['format'] ?? '') === 'VIDEO' || ($header['format'] ?? '') === 'DOCUMENT') {
            $this->templateVariables['header_media_url'] = $this->templateVariables['header_media_url'] ?? '';
        }
    }

    public function getLivePreviewTextProperty()
    {
        $template = $this->selectedTemplate;
        if (! $template && $this->selectedTemplateId) {
            $template = \App\Models\WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)
                ->find($this->selectedTemplateId);
        }

        if (! $template) {
            return e('Template preview unavailable.');
        }

        $components = $template->components ?? [];
        if (is_string($components)) {
            $components = json_decode($components, true) ?: [];
        }

        $bodyText = '';
        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                $bodyText = $component['text'] ?? '';
                break;
            }
        }

        if ($bodyText === '') {
            return e('No preview text available for this template.');
        }

        $text = e($bodyText);

        foreach ($this->templateVariables as $key => $value) {
            if ($key === 'header_media_url') {
                continue;
            }

            $placeholder = '{{'.$key.'}}';
            $replacement = $value !== '' ? e($value) : $placeholder;
            $text = str_replace(e($placeholder), $replacement, $text);
        }

        return $this->waMarkdown($text);
    }

    /**
     * Render WhatsApp markdown (bold/italic/strikethrough) to HTML.
     */
    private function waMarkdown(string $text): string
    {
        $text = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/_(.*?)_/', '<em>$1</em>', $text);

        return preg_replace('/~(.*?)~/', '<del>$1</del>', $text);
    }

    /**
     * Create an internal_note message on the current conversation.
     */
    private function logInternalNote(string $content, array $meta = []): \App\Models\Message
    {
        return \App\Models\Message::create([
            'team_id' => Auth::user()->currentTeam->id,
            'contact_id' => $this->conversation->contact_id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'sent',
            'type' => 'internal_note',
            'content' => $content,
            'metadata' => $meta,
        ]);
    }

    public function saveInternalNote()
    {
        $this->validate([
            'msgBody' => 'required|string',
        ]);

        if (! $this->conversation) {
            return;
        }

        $this->logInternalNote($this->msgBody, ['agent_id' => Auth::id(), 'agent_name' => Auth::user()->name]);

        $this->reset('msgBody');
        $this->loadConversation();
        $this->dispatch('messageSent');
    }

    public function transferConversation($agentId)
    {
        if (! $this->conversation) {
            return;
        }

        $agent = Auth::user()->currentTeam->users()->find($agentId);
        if (! $agent) {
            return;
        }

        $this->conversation->update([
            'assigned_to' => $agentId,
        ]);

        // Log transfer as internal note
        $this->logInternalNote("Conversation transferred to {$agent->name}.", ['type' => 'transfer', 'transferred_by' => Auth::user()->name]);

        $agent->notify(new \App\Notifications\ConversationAssignedNotification($this->conversation, Auth::user()));
        \App\Events\ConversationAssigned::dispatch($this->conversation, $agent, Auth::user());

        $this->loadConversation();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Conversation transferred to {$agent->name}",
        ]);
    }

    public function sendVoiceNote($audioFile)
    {
        // wire.upload('voiceNote', blob, callback) sets $this->voiceNote to a
        // TemporaryUploadedFile — $audioFile is just the temp filename string.
        // Kept off `newAttachment` so the composer's upload hook doesn't fire.
        $file = $this->voiceNote;
        if (! $this->conversation || ! $file) {
            return;
        }

        $disk = config('filesystems.default');

        // Store with an explicit .ogg extension. A blob upload has no filename, so
        // store() would save it extensionless and the server would serve it as
        // application/octet-stream — WhatsApp rejects that as a voice note.
        $path = $file->storeAs('voice-notes', \Illuminate\Support\Str::random(40).'.ogg', $disk);

        $message = \App\Models\Message::create([
            'team_id' => Auth::user()->currentTeam->id,
            'contact_id' => $this->conversation->contact_id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'queued',
            'type' => 'audio',
            'media_url' => $path,
            'media_type' => 'audio/ogg',
            'metadata' => [
                'agent_id' => Auth::id(),
                'agent_name' => Auth::user()->name,
            ],
        ]);

        \App\Jobs\SendMessageJob::dispatch($message);

        $this->reset('voiceNote');
        $this->loadConversation();
        $this->dispatch('messageSent');
    }

    public function closeConversation($reason = 'resolved')
    {
        if ($this->conversation) {
            $this->conversation->update([
                'status' => 'closed',
                'closed_at' => now(),
                'close_reason' => $reason,
            ]);

            // Trigger CSAT Survey
            try {
                app(\App\Services\CsatService::class)->sendSurvey($this->conversation);
            } catch (\Exception $e) {
                Log::warning("Failed to send CSAT survey for Conversation #{$this->conversation->id}: " . $e->getMessage());
            }

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
                'close_reason' => null,
            ]);

            // Create internal note
            $this->logInternalNote('Conversation reopened by '.Auth::user()->name, ['type' => 'reopen', 'reopened_by' => Auth::user()->name]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Conversation reopened successfully',
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
                'close_reason' => 'spam',
            ]);

            // Create internal note
            $this->logInternalNote('Conversation marked as spam by '.Auth::user()->name, ['type' => 'spam', 'marked_by' => Auth::user()->name]);

            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Conversation marked as spam',
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
                'blocked_by' => Auth::id(),
            ]);

            $this->conversation->update([
                'status' => 'closed',
                'close_reason' => 'contact_blocked',
            ]);

            // Create internal note
            $this->logInternalNote('Contact blocked by '.Auth::user()->name, ['type' => 'block', 'blocked_by' => Auth::user()->name]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Contact has been blocked',
            ]);
            $this->loadConversation();
        }
    }

    public function exportConversation()
    {
        if (! $this->conversation) {
            return;
        }

        $messages = $this->conversation->messages()->orderBy('created_at', 'asc')->get();
        $filename = "conversation_{$this->conversation->id}_".now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Timestamp', 'Sender', 'Type', 'Content', 'Status']);

            $this->conversation->messages()
                ->orderBy('created_at', 'asc')
                ->chunk(200, function ($messages) use ($handle) {
                    foreach ($messages as $msg) {
                        $sender = $msg->direction === 'inbound'
                            ? ($this->conversation->contact->name ?? $this->conversation->contact->phone_number)
                            : (isset($msg->metadata['agent_name']) ? "Agent ({$msg->metadata['agent_name']})" : 'System/Agent');

                        $content = $msg->content;
                        if ($msg->type !== 'text') {
                            $content = "[{$msg->type}] ".($msg->caption ?? 'Media asset: '.($msg->media_url ?? 'N/A'));
                        }

                        fputcsv($handle, [
                            $msg->created_at->format('Y-m-d H:i:s'),
                            $sender,
                            ucfirst($msg->type),
                            $content,
                            ucfirst($msg->status),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename);
    }

    public function openForwardModal($messageId)
    {
        $this->forwardingMessageId = $messageId;
        $this->forwardSelectedConversations = [];
        $this->forwardRecentConversations = \App\Models\Conversation::with('contact')
            ->where('team_id', Auth::user()->currentTeam->id)
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();
        $this->showForwardModal = true;
    }

    public function forwardMessage()
    {
        if (!$this->forwardingMessageId || empty($this->forwardSelectedConversations)) {
            return;
        }

        $sourceMessage = \App\Models\Message::find($this->forwardingMessageId);
        if (!$sourceMessage) return;

        foreach ($this->forwardSelectedConversations as $convId) {
            $conv = \App\Models\Conversation::find($convId);
            if (!$conv) continue;

            $message = \App\Models\Message::create([
                'team_id' => Auth::user()->currentTeam->id,
                'contact_id' => $conv->contact_id,
                'conversation_id' => $conv->id,
                'direction' => 'outbound',
                'status' => 'queued',
                'type' => $sourceMessage->type,
                'content' => $sourceMessage->content,
                'caption' => $sourceMessage->caption,
                'media_url' => $sourceMessage->media_url,
                'media_type' => $sourceMessage->media_type,
                'metadata' => ['agent_id' => Auth::id(), 'agent_name' => Auth::user()->name, 'is_forwarded' => true],
            ]);

            \App\Jobs\SendMessageJob::dispatch($message);
        }

        $this->showForwardModal = false;
        $this->forwardingMessageId = null;
        $this->forwardSelectedConversations = [];
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Message forwarded']);
    }

    public function saveCallNote($messageId, $note)
    {
        $message = \App\Models\Message::where('team_id', Auth::user()->currentTeam->id)
            ->where('id', $messageId)
            ->first();

        if ($message) {
            $metadata = $message->metadata;
            if (! is_array($metadata)) {
                $metadata = [];
            }
            $metadata['agent_note'] = $note;
            $metadata['note_saved_at'] = now()->timestamp;
            $message->update(['metadata' => $metadata]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Call note updated.',
            ]);
        }
    }

    public function toggleBot()
    {
        if (! $this->conversation || ! $this->conversation->contact) {
            return;
        }

        $contact = $this->conversation->contact;
        $handoff = new \App\Services\BotHandoffService;

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
        // Cancelled before sending — remove the orphaned file.
        if (! empty($this->newAttachmentData['path'])) {
            Storage::disk(config('filesystems.default'))->delete($this->newAttachmentData['path']);
        }
        $this->newAttachmentData = null;
        $this->reset('newAttachment');
    }

    public function toggleCategory($categoryId)
    {
        if (! $this->conversation) {
            return;
        }

        $metadata = $this->conversation->metadata;
        if (! is_array($metadata)) {
            $metadata = [];
        }
        $tags = $metadata['tags'] ?? [];

        if (in_array($categoryId, $tags)) {
            $tags = array_values(array_filter($tags, fn ($id) => $id != $categoryId));
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
        if (! Auth::check() || ! Auth::user()->currentTeam) {
            return;
        }

        $message = \App\Models\Message::where('team_id', Auth::user()->currentTeam->id)
            ->find($messageId);

        if (! $message || $message->conversation_id !== $this->conversationId) {
            return;
        }

        $metadata = $message->metadata;
        if (! is_array($metadata)) {
            $metadata = [];
        }

        $reactions = $metadata['reactions'] ?? [];
        if (! is_array($reactions)) {
            $reactions = [];
        }

        // Toggle or Add
        $myId = Auth::id();
        $emojiToSend = $emoji;

        if (($reactions[$myId] ?? null) === $emoji) {
            unset($reactions[$myId]);
            $emojiToSend = ''; // Empty string removes reaction in WA API
        } else {
            $reactions[$myId] = $emoji;
        }

        $metadata['reactions'] = $reactions;
        $message->update(['metadata' => $metadata]);

        if ($message->whatsapp_message_id) {
            try {
                $whatsapp = new \App\Services\WhatsAppService(\Illuminate\Support\Facades\Auth::user()->currentTeam);
                $response = $whatsapp->sendReaction($this->conversation->contact->phone_number, $message->whatsapp_message_id, $emojiToSend);
                \Illuminate\Support\Facades\Log::info('Reaction API Response: ' . json_encode($response));
                
                if (isset($response['error'])) {
                    \Illuminate\Support\Facades\Log::error('WhatsApp API Reaction Error: ' . json_encode($response['error']));
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send reaction to WhatsApp: ' . $e->getMessage());
            }
        }

        // Broadcast update
        \App\Events\MessageStatusUpdated::dispatch($message);
        $this->dispatch('message-reacted');
    }

    // Both re-run on every roundtrip (send, react, tag…) but change rarely.
    // ponytail: 60s TTL, swap for cache invalidation on save if staleness bites.
    public function getQuickRepliesProperty()
    {
        $teamId = Auth::user()->currentTeam->id;

        return cache()->remember("chat_quick_replies_{$teamId}", 60, fn () => \App\Models\CannedMessage::where('team_id', $teamId)
            ->latest()
            ->get(['shortcut', 'content'])
            ->map(fn ($msg) => ['code' => $msg->shortcut, 'text' => $msg->content])
            ->all());
    }

    public function getAgentsProperty()
    {
        $teamId = Auth::user()->currentTeam->id;

        return cache()->remember(
            "chat_agents_{$teamId}_".Auth::id(),
            60,
            fn () => Auth::user()->currentTeam->users()
                ->where('users.id', '!=', Auth::id())
                ->get(['users.id', 'users.name', 'users.email'])
        );
    }

    public function getIsSessionOpenProperty()
    {
        if (! $this->conversation || ! $this->conversation->contact) {
            return false;
        }
        $lastMsg = $this->conversation->contact->last_customer_message_at;

        return $lastMsg && $lastMsg->gt(now()->subHours(24));
    }

    public function getIsOptedOutProperty()
    {
        return $this->conversation?->contact?->opt_in_status === 'opted_out';
    }

    public function downloadTranscript()
    {
        if (! $this->conversation) {
            return;
        }

        $contactName = $this->conversation->contact?->name ?: 'Contact';
        $contactPhone = $this->conversation->contact?->phone_number ?: '';
        $messages = $this->conversation->messages()
            ->with(['user'])
            ->orderBy('created_at', 'asc')
            ->get();

        $lines = [];
        $lines[] = "=== CHAT TRANSCRIPT ===";
        $lines[] = "Contact: {$contactName} ({$contactPhone})";
        $lines[] = "Exported: ".now()->format('Y-m-d H:i:s');
        $lines[] = "----------------------------------------\n";

        foreach ($messages as $msg) {
            $sender = $msg->direction === 'inbound' ? $contactName : ($msg->user?->name ?: 'Agent');
            $time = $msg->created_at ? $msg->created_at->format('Y-m-d H:i:s') : '';
            $content = trim($msg->content ?: '['.strtoupper($msg->type ?? 'media').']');
            $lines[] = "[{$time}] {$sender}: {$content}";
        }

        $transcript = implode("\n", $lines);
        $filename = "transcript_conv_{$this->conversationId}.txt";

        return response()->streamDownload(fn () => print($transcript), $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function toggleStarMessage($messageId)
    {
        if (! Auth::check() || ! Auth::user()->currentTeam) {
            return;
        }

        $message = \App\Models\Message::where('team_id', Auth::user()->currentTeam->id)
            ->where('conversation_id', $this->conversationId)
            ->find($messageId);

        if ($message) {
            $message->update(['is_starred' => ! $message->is_starred]);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $message->is_starred ? 'Message starred' : 'Message unstarred',
            ]);
        }
    }

    public function getPreviewButtonBodyProperty()
    {
        return $this->waMarkdown(e($this->buttonBody ?: 'Message text...'));
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

        if (! $this->conversation) {
            return;
        }

        try {
            // Prepare buttons
            $buttons = [];
            foreach ($this->interactiveButtons as $title) {
                $id = 'btn_'.\Illuminate\Support\Str::slug($title);
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
        if (! $this->conversation) {
            if ($this->conversationId) {
                // Try to reload conversation if missing (Hydration issue safety)
                $this->loadConversation();
            }

            if (! $this->conversation) {
                \Log::error('MessageWindow: loadMessagesJson failed - No conversation found', ['id' => $this->conversationId]);

                return [];
            }
        }

        $messages = $this->conversation->messages()
            ->with(['attributedCampaign', 'replyTo', 'team'])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        return $messages
            ->map(function ($msg) {
                $team = $msg->relationLoaded('team') ? $msg->team : ($this->conversation?->team ?? \Illuminate\Support\Facades\Auth::user()?->currentTeam);
                // Media is fetched by the queued DownloadMediaJob on receipt. If it's
                // still missing here, re-queue it — but never download inline: this runs
                // inside the chat-render request and a Meta call (up to 60s each) would
                // block the whole page. Skip messages that already gave up so polling
                // doesn't re-queue them forever; the realtime event updates the bubble.
                if (empty($msg->media_url) && ! empty($msg->media_id) && $team
                    && ! ($msg->metadata['media_failed'] ?? false)) {
                    \App\Jobs\DownloadMediaJob::dispatch($msg->id, $msg->media_id, $team->id);
                }

                return [
                    'id' => $msg->id,
                    'direction' => $msg->direction,
                    'content' => $msg->content,
                    'type' => $msg->type,
                    'status' => $msg->status,
                    'created_at' => $msg->created_at->timestamp, // Unix for easier JS sort
                    'pretty_time' => $msg->created_at->format('H:i'),
                    'media_url' => $msg->full_media_url,
                    'media_type' => $msg->media_type,
                    'caption' => $msg->caption,
                    'error_message' => $msg->error_message, // For failed status
                    'is_outbound' => $msg->direction === 'outbound',
                    'attributed_campaign_name' => $msg->attributedCampaign?->name,
                    'metadata' => $msg->metadata,
                    'reply_to_message_id' => $msg->reply_to_message_id,
                    'reply_to_message' => $msg->replyTo ? [
                        'content' => $msg->replyTo->content ?: ucfirst($msg->replyTo->type),
                        'is_outbound' => $msg->replyTo->direction === 'outbound',
                    ] : null,
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
        if (empty($body)) {
            return ['status' => 'error', 'message' => 'Message body is empty'];
        }

        // Component state can be lost after long idle periods — reload before failing.
        if (! $this->conversation && $this->conversationId) {
            $this->loadConversation();
        }

        if (! $this->conversation) {
            return ['status' => 'error', 'message' => 'Conversation not found. Please refresh the page.'];
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
            'content' => $body,
            'metadata' => [
                'agent_id' => Auth::id(), 
                'agent_name' => Auth::user()->name,
                'reply_to_message_id' => $this->replyToMessageId,
            ],
        ];

        $message = \App\Models\Message::create($msgData);
        $this->replyToMessageId = null;

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

    #[\Livewire\Attributes\Renderless]
    public function retryMessage($messageId)
    {
        if (! $this->conversation && $this->conversationId) {
            $this->loadConversation();
        }

        if (! $this->conversation) {
            return ['status' => 'error', 'message' => 'Conversation not found. Please refresh the page.'];
        }

        $message = \App\Models\Message::where('id', $messageId)
            ->where('conversation_id', $this->conversation->id)
            ->first();

        if (! $message) {
            return ['status' => 'error', 'message' => 'Message not found'];
        }

        $message->update([
            'status' => 'queued',
            'error_message' => null,
        ]);

        \App\Jobs\SendMessageJob::dispatch($message);

        $message->refresh();

        return [
            'id' => $message->id,
            'status' => $message->status,
            'error_message' => $message->error_message,
        ];
    }

    #[\Livewire\Attributes\Renderless]
    public function retryMediaDownload($messageId)
    {
        \Illuminate\Support\Facades\Log::info("[LIVEWIRE_RETRY_MEDIA] Retry requested for Message #{$messageId}");

        $message = \App\Models\Message::find($messageId);
        if (! $message || ! $message->media_id) {
            \Illuminate\Support\Facades\Log::warning("[LIVEWIRE_RETRY_MEDIA] Message or media ID missing for Message #{$messageId}");
            return ['status' => 'error', 'message' => 'Message or media ID not found'];
        }

        $meta = is_array($message->metadata) ? $message->metadata : [];
        unset($meta['media_failed'], $meta['media_failed_reason']);
        $message->update(['metadata' => $meta]);

        try {
            \App\Jobs\DownloadMediaJob::dispatchSync($message->id, $message->media_id, $message->team_id);
            $message->refresh();
            \Illuminate\Support\Facades\Log::info("[LIVEWIRE_RETRY_MEDIA] Successfully downloaded media for Message #{$messageId}", [
                'media_url' => $message->full_media_url,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("[LIVEWIRE_RETRY_MEDIA] Media download failed for Message #{$messageId}", [
                'error' => $e->getMessage(),
                'media_id' => $message->media_id,
                'team_id' => $message->team_id,
                'trace' => $e->getTraceAsString(),
            ]);

            $meta = is_array($message->metadata) ? $message->metadata : [];
            $meta['media_failed'] = true;
            $meta['media_failed_reason'] = $e->getMessage();
            $message->update(['metadata' => $meta]);

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'message' => 'Media download failed: ' . $e->getMessage(),
                'media_url' => null,
                'metadata' => $message->fresh()->metadata,
            ];
        }

        \App\Events\MessageReceived::dispatch($message);

        return [
            'status' => 'success',
            'media_url' => $message->full_media_url,
            'metadata' => $message->metadata,
        ];
    }

    public function render()
    {
        return view('livewire.chat.message-window', [
            'isSessionOpen' => $this->isSessionOpen,
            'quickReplies' => $this->quickReplies,
            'agents' => $this->agents,
            'initialMessages' => $this->initialMessages, // null on re-renders; Alpine only reads it once
        ]);
    }
}

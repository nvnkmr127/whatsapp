<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        ])->where('team_id', Auth::user()->currentTeam->id)->find($this->conversationId);

        // Mark as read logic could go here
    }

    public function updatedNewAttachment()
    {
        $this->validate(['newAttachment' => 'max:16384']); // 16MB max
    }

    public function sendMessage()
    {
        $this->validate([
            'messageBody' => 'nullable|required_without:newAttachment|string',
            'newAttachment' => 'nullable|file|max:16384',
        ]);

        if (!$this->conversation)
            return;

        $waService = new WhatsAppService();
        $waService->setTeam(Auth::user()->currentTeam);

        // Send via API
        try {
            $response = null;

            if ($this->newAttachment) {
                // Handle Media
                $path = $this->newAttachment->store('media', 'public');
                $url = asset(Storage::url($path));
                $type = $this->getMediaType($this->newAttachment->getMimeType());

                $response = $waService->sendMedia(
                    $this->conversation->contact->phone_number,
                    $type,
                    $url,
                    $this->messageBody // Caption
                );

            } elseif ($this->messageBody) {
                // Text Only
                $response = $waService->sendText(
                    $this->conversation->contact->phone_number,
                    $this->messageBody
                );
            }

            if ($response && ($response['success'] ?? false)) {
                // Validated
                $this->reset(['messageBody', 'newAttachment']);
                $this->dispatch('messageSent'); // Trigger UI update if needed
            } else {
                $errorMsg = $response ? ($response['error']['message'] ?? 'Unknown error') : 'No content to send.';
                session()->flash('error', 'Failed to send: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
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

    public function handleIncomingMessage($event)
    {
        if ($this->conversation && $event['message']['conversation_id'] == $this->conversation->id) {
            $this->loadConversation();
            $this->dispatch('scroll-bottom');
        }
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

        $waService = new WhatsAppService();
        $waService->setTeam(Auth::user()->currentTeam);

        try {
            // Map variables to parameters structure required by WhatsApp/Service
            // Assuming service accepts an array of strings in order
            $parameters = [];

            // Sort variables by their index (1, 2, 3...)
            ksort($this->templateVariables);
            $parameters = array_values($this->templateVariables);

            // If template has a media header, the service expects the URL as the first parameter
            if ($this->templateMediaUrl) {
                array_unshift($parameters, $this->templateMediaUrl);
            }

            $response = $waService->sendTemplate(
                $this->conversation->contact->phone_number,
                $this->selectedTemplate->name,
                $this->selectedTemplate->language ?? 'en_US',
                $parameters // Pass variables here
            );

            if ($response['success'] ?? false) {
                $this->dispatch('messageSent');
                $this->loadConversation();
                $this->closeTemplateModals();
                session()->flash('success', 'Template sent successfully!');
            } else {
                $errorMsg = $response['error']['message'] ?? 'Unknown error';
                session()->flash('error', 'Template failed: ' . $errorMsg);
            }
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

        $waService = new WhatsAppService();
        $waService->setTeam(Auth::user()->currentTeam);

        try {
            // Prepare buttons: ID is just the title slugified or similar, here we use text as ID
            $buttons = [];
            foreach ($this->interactiveButtons as $title) {
                $id = 'btn_' . \Illuminate\Support\Str::slug($title);
                $buttons[$id] = $title;
            }

            $response = $waService->sendInteractiveButtons(
                $this->conversation->contact->phone_number,
                $this->buttonBody,
                $buttons
            );

            if ($response['success'] ?? false) {
                $this->dispatch('messageSent');
                $this->loadConversation();
                $this->showInteractiveButtonsModal = false;
                $this->reset(['buttonBody', 'interactiveButtons']);
                session()->flash('success', 'Buttons sent successfully!');
            } else {
                $errorMsg = $response['error']['message'] ?? 'Unknown error';
                session()->flash('error', 'Failed to send buttons: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
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

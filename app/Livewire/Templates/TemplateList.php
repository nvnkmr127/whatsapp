<?php

namespace App\Livewire\Templates;

use App\Models\WhatsappTemplate;
use App\Traits\WhatsApp;
use Livewire\Component;
use Livewire\WithPagination;

class TemplateList extends Component
{
    use WhatsApp;
    use WithPagination;

    public $search = '';
    public $syncing = false;
    public $showCreateModal = false;
    public $showViewModal = false;

    // Form Fields (Shared for Create/View, View is Read-Only)
    public $name = '';
    public $category = 'UTILITY';
    public $language = 'en_US';
    public $headerType = 'NONE'; // NONE, TEXT
    public $headerText = '';
    public $body = '';
    public $footer = '';
    public $buttons = [];
    public $exampleMediaUrl = '';

    protected $listeners = ['refreshComponent' => '$refresh'];

    protected $rules = [
        'name' => 'required|regex:/^[a-z0-9_]+$/|max:512',
        'category' => 'required|in:UTILITY,MARKETING,AUTHENTICATION',
        'language' => 'required|string',
        'body' => 'required|max:1024',
        'headerText' => 'required_if:headerType,TEXT|max:60',
        'footer' => 'nullable|max:60',
        'buttons.*.text' => 'required|string|max:25',
        'buttons.*.url' => 'required_if:buttons.*.type,URL',
        'buttons.*.phoneNumber' => 'required_if:buttons.*.type,PHONE_NUMBER',
    ];

    // Reset pagination when searching
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function syncTemplates()
    {
        $this->syncing = true;
        // Check if settings allow sync (for now assuming yes or handling gracefully)
        $response = $this->loadTemplatesFromWhatsApp();

        if ($response['status']) {
            $this->dispatch('notify', message: 'Templates synced: ' . ($response['count'] ?? 0), type: 'success');
        } else {
            $this->dispatch('notify', message: 'Sync failed: ' . ($response['message'] ?? 'Unknown error'), type: 'error');
        }
        $this->syncing = false;
    }

    public function openCreateModal()
    {
        $this->reset(['name', 'category', 'language', 'headerType', 'headerText', 'body', 'footer', 'buttons']);
        $this->showCreateModal = true;
    }

    public function addButton()
    {
        if (count($this->buttons) >= 3)
            return;
        $this->buttons[] = ['type' => 'QUICK_REPLY', 'text' => '', 'url' => '', 'phoneNumber' => ''];
    }

    public function removeButton($index)
    {
        unset($this->buttons[$index]);
        $this->buttons = array_values($this->buttons);
    }

    public function viewTemplate($id)
    {
        $template = WhatsappTemplate::find($id);
        if (!$template)
            return;

        $this->name = $template->name;
        $this->category = $template->category;
        $this->language = $template->language;

        // Parse components to populate fields
        $components = is_string($template->components) ? json_decode($template->components, true) : $template->components;

        $this->headerType = 'NONE';
        $this->headerText = '';
        $this->body = '';
        $this->footer = '';
        $this->buttons = [];

        if (is_array($components)) {
            foreach ($components as $component) {
                if ($component['type'] === 'HEADER') {
                    $this->headerType = $component['format'] ?? 'NONE';
                    if ($this->headerType === 'TEXT') {
                        $this->headerText = $component['text'] ?? '';
                    }
                }
                if ($component['type'] === 'BODY') {
                    $this->body = $component['text'] ?? '';
                }
                if ($component['type'] === 'FOOTER') {
                    $this->footer = $component['text'] ?? '';
                }
                if ($component['type'] === 'BUTTONS') {
                    foreach ($component['buttons'] as $btn) {
                        $this->buttons[] = [
                            'type' => $btn['type'],
                            'text' => $btn['text'],
                            'url' => $btn['url'] ?? '',
                            'phoneNumber' => $btn['phone_number'] ?? '',
                        ];
                    }
                }
            }
        }

        $this->showViewModal = true;
    }

    public function createTemplate(\App\Services\WhatsAppService $whatsapp)
    {
        $this->validate();

        $components = [];

        // Header
        if ($this->headerType !== 'NONE') {
            $header = [
                'type' => 'HEADER',
                'format' => $this->headerType,
            ];

            if ($this->headerType === 'TEXT') {
                $header['text'] = $this->headerText;
            } else {
                // Media headers need examples
                $header['example'] = [
                    'header_handle' => [
                        $this->exampleMediaUrl ?: 'https://scontent.xx.fbcdn.net/v/...'
                    ]
                ];
            }
            $components[] = $header;
        }

        // Body
        $components[] = [
            'type' => 'BODY',
            'text' => $this->body
        ];

        // Footer
        if ($this->footer) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $this->footer
            ];
        }

        // Buttons
        if (!empty($this->buttons)) {
            $buttonComponents = [];
            foreach ($this->buttons as $btn) {
                if ($btn['type'] === 'QUICK_REPLY') {
                    $buttonComponents[] = [
                        'type' => 'QUICK_REPLY',
                        'text' => $btn['text']
                    ];
                } elseif ($btn['type'] === 'URL') {
                    $buttonComponents[] = [
                        'type' => 'URL',
                        'text' => $btn['text'],
                        'url' => $btn['url']
                    ];
                } elseif ($btn['type'] === 'PHONE_NUMBER') {
                    $buttonComponents[] = [
                        'type' => 'PHONE_NUMBER',
                        'text' => $btn['text'],
                        'phone_number' => $btn['phoneNumber']
                    ];
                }
            }
            $components[] = [
                'type' => 'BUTTONS',
                'buttons' => $buttonComponents
            ];
        }

        $payload = [
            'name' => $this->name,
            'category' => $this->category,
            'language' => $this->language,
            'components' => $components,
        ];

        try {
            $whatsapp->setTeam(auth()->user()->currentTeam);
            $response = $whatsapp->createTemplate($payload);

            if ($response['success']) {
                $this->showCreateModal = false;
                $this->syncTemplates();
                $this->dispatch('notify', message: 'Template created successfully!', type: 'success');
            } else {
                $errorMsg = $response['error']['message'] ?? json_encode($response['error']);
                $this->dispatch('notify', message: 'Meta Error: ' . $errorMsg, type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Error: ' . $e->getMessage());
        }
    }

    public function deleteTemplate($id, \App\Services\WhatsAppService $whatsapp)
    {
        $template = WhatsappTemplate::find($id);
        if (!$template)
            return;

        try {
            // Delete from Meta
            $whatsapp->setTeam(auth()->user()->currentTeam);
            $response = $whatsapp->deleteTemplate($template->name);

            if ($response['success']) {
                $template->delete();
                $this->dispatch('notify', message: 'Template deleted successfully.', type: 'success');
            } else {
                // If template not found on Meta, maybe just delete local?
                // For now, respect error.
                $errorMsg = $response['error']['message'] ?? json_encode($response['error']);
                $this->dispatch('notify', message: 'Meta Error: ' . $errorMsg, type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Error: ' . $e->getMessage());
        }
    }

    public function getHeaderType($template)
    {
        $components = is_array($template->components) ? $template->components : json_decode($template->components, true);
        foreach ($components ?? [] as $c) {
            if (($c['type'] ?? '') === 'HEADER') {
                return $c['format'] ?? 'TEXT';
            }
        }
        return 'NONE';
    }

    public function render()
    {
        $query = WhatsappTemplate::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('category', 'like', '%' . $this->search . '%');
        }

        $templates = $query->latest()->paginate(10);

        return view('livewire.templates.template-list', [
            'templates' => $templates
        ]);
    }
}

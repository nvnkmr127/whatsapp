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

    protected $listeners = ['refreshComponent' => '$refresh'];

    protected $rules = [
        'name' => 'required|regex:/^[a-z0-9_]+$/',
        'category' => 'required',
        'language' => 'required',
        'body' => 'required',
        'headerText' => 'required_if:headerType,TEXT',
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
            $this->dispatch('notify', 'Templates synced: ' . ($response['count'] ?? 0));
        } else {
            $this->dispatch('notify', 'Sync failed: ' . ($response['message'] ?? 'Unknown error'));
        }
        $this->syncing = false;
    }

    public function openCreateModal()
    {
        $this->reset(['name', 'category', 'language', 'headerType', 'headerText', 'body', 'footer']);
        $this->showCreateModal = true;
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

        if (is_array($components)) {
            foreach ($components as $component) {
                if ($component['type'] === 'HEADER') {
                    if (($component['format'] ?? '') === 'TEXT') {
                        $this->headerType = 'TEXT';
                        $this->headerText = $component['text'] ?? '';
                    }
                }
                if ($component['type'] === 'BODY') {
                    $this->body = $component['text'] ?? '';
                }
                if ($component['type'] === 'FOOTER') {
                    $this->footer = $component['text'] ?? '';
                }
            }
        }

        $this->showViewModal = true;
    }

    public function createTemplate()
    {
        $this->validate();

        $components = [];

        // Header
        if ($this->headerType === 'TEXT' && $this->headerText) {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $this->headerText
            ];
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

        $payload = [
            'name' => $this->name,
            'category' => $this->category,
            'language' => $this->language,
            'components' => $components,
        ];

        // Use Trait to create on Meta
        $response = $this->createWhatsAppTemplate($payload);

        if ($response['status']) {
            $this->showCreateModal = false;
            $this->syncTemplates(); // Re-sync to save to DB
            $this->dispatch('notify', 'Template created successfully!');
        } else {
            $this->dispatch('notify', 'Meta Error: ' . $response['message']);
        }
    }

    public function deleteTemplate($id)
    {
        $template = WhatsappTemplate::find($id);
        if (!$template)
            return;

        // Delete from Meta
        $response = $this->deleteWhatsAppTemplate($template->name);

        if ($response['status']) {
            $template->delete();
            $this->dispatch('notify', 'Template deleted successfully.');
        } else {
            $this->dispatch('notify', 'Meta Error: ' . $response['message']);
        }
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

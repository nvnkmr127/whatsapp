<?php

namespace App\Livewire\Templates;

use App\Services\WhatsAppService;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TemplateManager extends Component
{
    public $templates = [];
    public $showCreateModal = false;

    // Form Fields
    public $name = '';
    public $category = 'UTILITY';
    public $language = 'en_US';
    public $headerType = 'NONE'; // NONE, TEXT, IMAGE
    public $headerText = '';
    public $body = '';
    public $footer = '';

    public function mount()
    {
        $this->loadTemplates();
    }

    public function loadTemplates()
    {
        $this->templates = WhatsAppTemplate::where('team_id', Auth::user()->currentTeam->id)->latest()->get();
    }

    public function syncTemplates(WhatsAppService $whatsapp)
    {
        try {
            $whatsapp->setTeam(Auth::user()->currentTeam);
            $response = $whatsapp->getTemplates();

            if ($response['success']) {
                foreach ($response['data'] as $remote) {
                    WhatsAppTemplate::updateOrCreate(
                        [
                            'team_id' => Auth::user()->currentTeam->id,
                            'name' => $remote['name'],
                            'language' => $remote['language'],
                        ],
                        [
                            'category' => $remote['category'],
                            'status' => $remote['status'],
                            'whatsapp_template_id' => $remote['id'] ?? null,
                            'components' => $remote['components'],
                        ]
                    );
                }
                session()->flash('success', 'Templates synced successfully!');
            } else {
                session()->flash('error', 'Failed to fetch templates: ' . json_encode($response['error']));
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->loadTemplates();
    }

    public function createTemplate(WhatsAppService $whatsapp)
    {
        $this->validate([
            'name' => 'required|regex:/^[a-z0-9_]+$/',
            'body' => 'required',
        ]);

        $components = [];

        // Header
        if ($this->headerType === 'TEXT' && $this->headerText) {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $this->headerText
            ];
        }

        // Body (Required)
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

        // Construct Payload
        $payload = [
            'name' => $this->name,
            'category' => $this->category,
            'language' => $this->language,
            'components' => $components,
        ];

        try {
            $whatsapp->setTeam(Auth::user()->currentTeam);
            $response = $whatsapp->createTemplate($payload);

            if ($response['success']) {
                $this->showCreateModal = false;
                $this->syncTemplates($whatsapp); // Refresh local DB
                session()->flash('success', 'Template submitted to Meta!');
            } else {
                $this->addError('name', 'Meta API Error: ' . json_encode($response['error']));
            }
        } catch (\Exception $e) {
            $this->addError('name', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.templates.template-manager');
    }
}

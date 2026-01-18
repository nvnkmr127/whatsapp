<?php

namespace App\Livewire\Templates;

use App\Services\WhatsAppService;
use App\Models\WhatsappTemplate;
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
    public $headerType = 'NONE'; // NONE, TEXT, IMAGE, VIDEO, DOCUMENT
    public $headerText = '';
    public $body = '';
    public $footer = '';
    public $buttons = [];

    // Validation
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

    public function mount()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-templates');
        $this->loadTemplates();
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

    public function loadTemplates()
    {
        $this->templates = WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)->latest()->get();
    }

    public function syncTemplates(WhatsAppService $whatsapp)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-templates');
        try {
            $whatsapp->setTeam(Auth::user()->currentTeam);
            $response = $whatsapp->getTemplates();

            if ($response['success']) {
                foreach ($response['data'] as $remote) {
                    WhatsappTemplate::updateOrCreate(
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
        \Illuminate\Support\Facades\Gate::authorize('manage-templates');
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
                // For IMAGE, VIDEO, DOCUMENT, we need an example (Meta requirement)
                // Here we can use a dummy URL for the example or keep it empty if API allows
                // Meta API usually requires 'example' for media headers
                $header['example'] = [
                    'header_handle' => [
                        'https://scontent.xx.fbcdn.net/v/...' // Placeholder or real handle
                    ]
                ];
                // Actually, simplify for now: Meta allows creation without examples via API sometimes, 
                // but let's just send format.
            }
            $components[] = $header;
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
                $this->reset(['name', 'headerText', 'body', 'footer', 'buttons', 'headerType', 'showCreateModal']);
                $this->syncTemplates($whatsapp); // Refresh local DB
                session()->flash('success', 'Template submitted to Meta!');
            } else {
                $errorMsg = $response['error']['error_user_msg'] ?? $response['error']['message'] ?? json_encode($response['error']);
                $this->addError('name', 'Meta API Error: ' . $errorMsg);
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

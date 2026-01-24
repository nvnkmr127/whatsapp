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
    public $copyCode = '';

    public $languages = [
        'af' => 'Afrikaans',
        'sq' => 'Albanian',
        'ar' => 'Arabic',
        'hy' => 'Armenian',
        'az' => 'Azerbaijani',
        'eu' => 'Basque',
        'be' => 'Belarusian',
        'bn' => 'Bengali',
        'bg' => 'Bulgarian',
        'ca' => 'Catalan',
        'zh_CN' => 'Chinese (CHN)',
        'zh_HK' => 'Chinese (HKG)',
        'zh_TW' => 'Chinese (TAI)',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'nl' => 'Dutch',
        'en' => 'English',
        'en_GB' => 'English (UK)',
        'en_US' => 'English (US)',
        'et' => 'Estonian',
        'fil' => 'Filipino',
        'fi' => 'Finnish',
        'fr' => 'French',
        'gl' => 'Galician',
        'ka' => 'Georgian',
        'de' => 'German',
        'el' => 'Greek',
        'gu' => 'Gujarati',
        'ha' => 'Hausa',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hu' => 'Hungarian',
        'is' => 'Icelandic',
        'id' => 'Indonesian',
        'ga' => 'Irish',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'kn' => 'Kannada',
        'kk' => 'Kazakh',
        'km' => 'Khmer',
        'ko' => 'Korean',
        'ky_KG' => 'Kyrgyz',
        'lo' => 'Lao',
        'lv' => 'Latvian',
        'lt' => 'Lithuanian',
        'mk' => 'Macedonian',
        'ms' => 'Malay',
        'ml' => 'Malayalam',
        'mr' => 'Marathi',
        'my' => 'Burmese',
        'nb' => 'Norwegian',
        'fa' => 'Persian',
        'pl' => 'Polish',
        'pt_BR' => 'Portuguese (BR)',
        'pt_PT' => 'Portuguese (POR)',
        'pa' => 'Punjabi',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sr' => 'Serbian',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'es' => 'Spanish',
        'es_AR' => 'Spanish (ARG)',
        'es_ES' => 'Spanish (SPA)',
        'es_MX' => 'Spanish (MEX)',
        'sw' => 'Swahili',
        'sv' => 'Swedish',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        'vi' => 'Vietnamese',
        'zu' => 'Zulu',
    ];

    protected $listeners = ['refreshComponent' => '$refresh'];

    protected $rules = [
        'name' => 'required|regex:/^[a-z0-9_]+$/|max:512',
        'category' => 'required|in:UTILITY,MARKETING,AUTHENTICATION',
        'language' => 'required|string',
        'body' => 'required|max:1024',
        'headerType' => 'in:NONE,TEXT,IMAGE,VIDEO,DOCUMENT,LOCATION',
        'headerText' => 'required_if:headerType,TEXT|max:60',
        'footer' => 'nullable|max:60',
        'buttons.*.text' => 'required|string|max:25',
        'buttons.*.type' => 'required|in:QUICK_REPLY,URL,PHONE_NUMBER,COPY_CODE,CATALOG,MPM',
        'buttons.*.url' => 'required_if:buttons.*.type,URL',
        'buttons.*.phoneNumber' => 'required_if:buttons.*.type,PHONE_NUMBER',
        'buttons.*.copyCode' => 'required_if:buttons.*.type,COPY_CODE|max:15',
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
        if (count($this->buttons) >= 10)
            return;
        $this->buttons[] = ['type' => 'QUICK_REPLY', 'text' => '', 'url' => '', 'phoneNumber' => '', 'copyCode' => ''];
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
            } else if (in_array($this->headerType, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                // Media headers need examples
                $header['example'] = [
                    'header_handle' => [
                        $this->exampleMediaUrl ?: '4'
                    ]
                ];
            }
            // LOCATION requires no extra creation example
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
                } elseif ($btn['type'] === 'COPY_CODE') {
                    $buttonComponents[] = [
                        'type' => 'COPY_CODE',
                        'example' => $btn['copyCode']
                    ];
                } elseif ($btn['type'] === 'CATALOG') {
                    $buttonComponents[] = [
                        'type' => 'CATALOG',
                        'text' => $btn['text']
                    ];
                } elseif ($btn['type'] === 'MPM') {
                    $buttonComponents[] = [
                        'type' => 'MPM',
                        'text' => $btn['text']
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

        // Module-Level Core Metrics
        $teamId = auth()->user()->current_team_id;
        $stats = [
            'approved' => WhatsappTemplate::where('team_id', $teamId)->where('status', 'APPROVED')->count(),
            'rejected' => WhatsappTemplate::where('team_id', $teamId)->where('status', 'REJECTED')->count(),
            'total' => WhatsappTemplate::where('team_id', $teamId)->count(),
            'media_ratio' => WhatsappTemplate::where('team_id', $teamId)->where('components', 'like', '%IMAGE%')->orWhere('components', 'like', '%VIDEO%')->count(),
        ];

        return view('livewire.templates.template-list', [
            'templates' => $templates,
            'stats' => $stats
        ]);
    }
}

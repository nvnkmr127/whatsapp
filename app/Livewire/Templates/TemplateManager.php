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

    public $languages = [
        'af' => 'Afrikaans',
        'sq' => 'Albanian',
        'ar' => 'Arabic',
        'az' => 'Azerbaijani',
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
        'ka' => 'Georgian',
        'de' => 'German',
        'el' => 'Greek',
        'gu' => 'Gujarati',
        'ha' => 'Hausa',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hu' => 'Hungarian',
        'id' => 'Indonesian',
        'ga' => 'Irish',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'kn' => 'Kannada',
        'kk' => 'Kazakh',
        'rw_RW' => 'Kinyarwanda',
        'ko' => 'Korean',
        'ky_KG' => 'Kyrgyz (Kyrgyzstan)',
        'lo' => 'Lao',
        'lv' => 'Latvian',
        'lt' => 'Lithuanian',
        'mk' => 'Macedonian',
        'ms' => 'Malay',
        'ml' => 'Malayalam',
        'mr' => 'Marathi',
        'nb' => 'Norwegian',
        'fa' => 'Persian',
        'pl' => 'Polish',
        'pt_BR' => 'Portuguese (Brazil)',
        'pt_PT' => 'Portuguese (Portugal)',
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

    // Validation
    protected function rules()
    {
        return [
            'name' => 'required|regex:/^[a-z0-9_]+$/|max:512',
            'category' => 'required|in:UTILITY,MARKETING,AUTHENTICATION',
            'language' => ['required', 'string', 'in:' . implode(',', array_keys($this->languages))],
            'body' => 'required|max:1024',
            'headerText' => 'required_if:headerType,TEXT|max:60',
            'footer' => 'nullable|max:60',
            'buttons.*.text' => 'required|string|max:25',
            'buttons.*.url' => 'required_if:buttons.*.type,URL',
            'buttons.*.phoneNumber' => 'required_if:buttons.*.type,PHONE_NUMBER',
        ];
    }

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

    public $variableConfig = [];

    // ... (existing properties)

    // Hook into updates to auto-detect variables
    public function updatedBody()
    {
        $this->detectVariables();
    }
    public function updatedHeaderText()
    {
        $this->detectVariables();
    }
    public function updatedButtons()
    {
        $this->detectVariables();
    }

    public function detectVariables()
    {
        $text = $this->body . ' ' . ($this->headerType === 'TEXT' ? $this->headerText : '');

        foreach ($this->buttons as $btn) {
            if (($btn['type'] ?? '') === 'URL') {
                $text .= ' ' . ($btn['url'] ?? '');
            }
        }

        $service = new \App\Services\TemplateService();
        $vars = $service->extractVariables($text);

        // Sync config: Add new, remove old
        $newConfig = [];
        foreach ($vars as $var) {
            if (isset($this->variableConfig[$var])) {
                $newConfig[$var] = $this->variableConfig[$var];
            } else {
                // Initialize default config
                $newConfig[$var] = [
                    'name' => '',
                    'type' => 'TEXT',
                    'fallback' => '',
                    'sample' => ''
                ];
            }
        }
        $this->variableConfig = $newConfig;
    }

    // Computed Property for Preview
    public function getPreviewBodyProperty()
    {
        $text = $this->body;
        if (empty($text))
            return '';

        foreach ($this->variableConfig as $var => $config) {
            $replacement = $config['sample'] ?: ($config['fallback'] ?: $var);
            // Replace {{1}} with the sample value
            $text = str_replace($var, $replacement, $text);
        }
        return $text;
    }

    public function createTemplate(WhatsAppService $whatsapp)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-templates');

        // Add dynamic rules for variables
        $rules = $this->rules();
        foreach ($this->variableConfig as $var => $config) {
            $rules['variableConfig.' . $var . '.name'] = 'required|regex:/^[a-z0-9_]+$/|max:50';
            $rules['variableConfig.' . $var . '.sample'] = 'required|max:100';
        }
        $this->validate($rules);

        // ... (rest of creation logic unchanged until success)
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
                // Meta API requires 'example' for media headers during creation
                // We'll use a placeholder handle if the user hasn't provided one (MVP simplification)
                // In a production app, the user would upload a file first to get a handle.
                $header['example'] = [
                    'header_handle' => [
                        '4' // Meta documentation often uses a dummy handle ID for validation if not live
                    ]
                ];
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

                // 1. Sync to get the new ID and component structure
                $this->syncTemplates($whatsapp);

                // 2. Find the newly created template and save variable_config
                // We identify it by name/language/team since we just created it.
                $tpl = WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)
                    ->where('name', $payload['name'])
                    ->where('language', $payload['language'])
                    ->first();

                if ($tpl) {
                    $tpl->update(['variable_config' => $this->variableConfig]);
                }

                // Reset config after save
                $this->variableConfig = [];

                session()->flash('success', 'Template submitted to Meta and Schema saved!');
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

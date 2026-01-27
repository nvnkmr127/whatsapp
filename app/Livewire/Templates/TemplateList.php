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
    use \Livewire\WithFileUploads;

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
    public $headerMedia; // For file uploads
    public $exampleMediaUrl = ''; // Keep for view-only or fallback
    public $copyCode = '';
    public $variableConfig = [];
    public $validationWarnings = [];
    public $ignoreWarnings = false;

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

    protected function rules()
    {
        return [
            'name' => 'required|regex:/^[a-z0-9_]+$/|max:512',
            'category' => 'required|in:UTILITY,MARKETING,AUTHENTICATION',
            'language' => 'required|string',
            'body' => 'required|max:1024',
            'headerType' => 'in:NONE,TEXT,IMAGE,VIDEO,DOCUMENT,LOCATION',
            'headerText' => 'required_if:headerType,TEXT|max:60',
            // Media Validation
            'headerMedia' => 'required_if:headerType,IMAGE,VIDEO,DOCUMENT|max:10240', // Max 10MB
            'footer' => 'nullable|max:60',
            'buttons.*.text' => 'required|string|max:25',
            'buttons.*.type' => 'required|in:QUICK_REPLY,URL,PHONE_NUMBER,COPY_CODE,CATALOG,MPM',
            'buttons.*.url' => 'required_if:buttons.*.type,URL',
            'buttons.*.phoneNumber' => 'required_if:buttons.*.type,PHONE_NUMBER',
            'buttons.*.copyCode' => 'required_if:buttons.*.type,COPY_CODE|max:15',
        ];
    }

    // Reset pagination when searching
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function syncTemplates(\App\Services\WhatsAppService $whatsapp)
    {
        $this->syncing = true;
        try {
            $whatsapp->setTeam(auth()->user()->currentTeam);
            $response = $whatsapp->getTemplates();

            if ($response['success']) {
                $syncedNames = [];
                foreach ($response['data'] as $remote) {
                    WhatsappTemplate::updateOrCreate(
                        [
                            'team_id' => auth()->user()->currentTeam->id,
                            'name' => $remote['name'],
                            'language' => $remote['language'],
                        ],
                        [
                            'category' => $remote['category'],
                            'status' => $remote['status'],
                            'whatsapp_template_id' => $remote['id'] ?? null,
                            'components' => $remote['components'] ?? [],
                        ]
                    );
                    $syncedNames[] = $remote['name'];
                }

                // Prune deleted templates
                WhatsappTemplate::where('team_id', auth()->user()->currentTeam->id)
                    ->whereNotIn('name', $syncedNames)
                    ->delete();

                $this->dispatch('notify', message: 'Templates synced and pruned successfully!', type: 'success');
            } else {
                $this->dispatch('notify', message: 'Sync failed: ' . json_encode($response['error']), type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
        $this->syncing = false;
    }

    public function openCreateModal()
    {
        $this->reset(['name', 'category', 'language', 'headerType', 'headerText', 'body', 'footer', 'buttons', 'headerMedia', 'validationWarnings', 'ignoreWarnings']);
        $this->showCreateModal = true;
    }

    public function addButton()
    {
        // Button Limit Logic
        $qrCount = collect($this->buttons)->where('type', 'QUICK_REPLY')->count();
        $ctaCount = collect($this->buttons)->whereIn('type', ['URL', 'PHONE_NUMBER', 'COPY_CODE'])->count();

        // If we have Mixed types, that's invalid, but we'll enforce that via validation or UI.
        // Hard Limit: 10 total (WhatsApp limit), but usually less relative to mix.
        // We will default to QUICK_REPLY unless we already have CTAs.

        if (count($this->buttons) >= 10)
            return;

        $type = 'QUICK_REPLY';
        if ($ctaCount > 0)
            $type = 'URL'; // Stick to CTA if started

        $this->buttons[] = ['type' => $type, 'text' => '', 'url' => '', 'phoneNumber' => '', 'copyCode' => ''];
        $this->detectVariables();
    }

    public function removeButton($index)
    {
        unset($this->buttons[$index]);
        $this->buttons = array_values($this->buttons);
        $this->detectVariables();
    }

    public function updatedBody()
    {
        $this->detectVariables();
    }

    public function updatedHeaderText()
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

    public function getPreviewBodyProperty()
    {
        $text = $this->body;
        if (empty($text))
            return '';

        foreach ($this->variableConfig as $var => $config) {
            $replacement = $config['sample'] ?: ($config['fallback'] ?: $var);
            $text = str_replace($var, $replacement, $text);
        }
        return $text;
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
        // Add dynamic rules for variables
        $rules = $this->rules(); // Check method now
        foreach ($this->variableConfig as $var => $config) {
            $rules['variableConfig.' . $var . '.name'] = 'required|regex:/^[a-z0-9_]+$/|max:50';
            $rules['variableConfig.' . $var . '.sample'] = 'required|max:100';
        }

        // Custom Validation for Mixed Buttons
        $qrCount = collect($this->buttons)->where('type', 'QUICK_REPLY')->count();
        $ctaCount = collect($this->buttons)->whereIn('type', ['URL', 'PHONE_NUMBER', 'COPY_CODE'])->count();

        if ($qrCount > 0 && $ctaCount > 0) {
            $this->addError('buttons', 'Cannot mix Quick Reply and Call-to-Action buttons.');
            return;
        }
        if ($qrCount > 3) {
            $this->addError('buttons', 'Max 3 Quick Reply buttons allowed.');
            return;
        }
        if ($ctaCount > 2) {
            $this->addError('buttons', 'Max 2 Call-to-Action buttons allowed.');
            return;
        }

        $this->validate($rules);

        $components = [];

        // Header
        if ($this->headerType !== 'NONE') {
            $header = [
                'type' => 'HEADER',
                'format' => $this->headerType,
            ];

            if ($this->headerType === 'TEXT') {
                $header['text'] = $this->headerText;
                // HEADER VARIABLES
                $service = new \App\Services\TemplateService();
                $vars = $service->extractVariables($this->headerText);
                if (!empty($vars)) {
                    $examples = [];
                    foreach ($vars as $var) {
                        $examples[] = $this->variableConfig[$var]['sample'] ?? 'sample';
                    }
                    $header['example'] = ['header_text' => $examples];
                }
            } else if (in_array($this->headerType, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                // UPLOAD MEDIA TO META
                try {
                    $handle = $whatsapp->uploadMediaForTemplate($this->headerMedia);
                    $header['example'] = [
                        'header_handle' => [$handle]
                    ];
                } catch (\Exception $e) {
                    $this->addError('headerMedia', 'Upload Failed: ' . $e->getMessage());
                    return;
                }
            }
            $components[] = $header;
        }

        // Body
        $bodyComponent = [
            'type' => 'BODY',
            'text' => $this->body
        ];

        // BODY VARIABLES
        $service = new \App\Services\TemplateService(); // Reuse or new
        $vars = $service->extractVariables($this->body);
        if (!empty($vars)) {
            $examples = [];
            foreach ($vars as $var) {
                $examples[] = $this->variableConfig[$var]['sample'] ?? 'sample';
            }
            // Body expects array of arrays
            $bodyComponent['example'] = ['body_text' => [$examples]];
        }

        $components[] = $bodyComponent;

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

        // --- PRE-SUBMISSION LINTING ---
        if (!$this->ignoreWarnings) {
            $tempTemplate = new WhatsappTemplate([
                'name' => $this->name,
                'category' => $this->category,
                'status' => 'APPROVED', // Assume approved for lifecycle checks
                'is_paused' => false,
                'components' => $components // Model casts this to array/json automatically if configured? 
                // Wait, model might expect JSON string or array depending on casts. 
                // Let's pass array, assuming strict validation handles it or we cast to array manually in validator.
                // TemplateValidator expects array for components in logic: $template->components ?? []
            ]);
            // Force component attribute storage if model doesn't cast on fill
            $tempTemplate->components = $components;

            $validator = new \App\Validators\TemplateValidator();
            // We pass [] for runtimeParams as this is static check
            $result = $validator->validate($tempTemplate, []);

            if (!$result->isValid()) {
                $this->validationWarnings = array_map(function ($e) {
                    return ['code' => $e->code, 'message' => $e->message, 'severity' => $e->severity];
                }, $result->getErrors());
                return; // HALT for user review
            }
        }

        try {
            $whatsapp->setTeam(auth()->user()->currentTeam);
            $response = $whatsapp->createTemplate($payload);

            if ($response['success']) {
                $this->showCreateModal = false;

                // 1. Sync to get the new structure
                $this->syncTemplates($whatsapp);

                // 2. Save variable_config
                $tpl = WhatsappTemplate::where('team_id', auth()->user()->currentTeam->id)
                    ->where('name', $payload['name'])
                    ->where('language', $payload['language'])
                    ->first();

                if ($tpl) {
                    $tpl->update(['variable_config' => $this->variableConfig]);
                }

                $this->variableConfig = [];
                $this->dispatch('notify', message: 'Template submitted and schema saved!', type: 'success');
            } else {
                $errorMsg = $response['error']['message'] ?? json_encode($response['error']);
                $this->dispatch('notify', message: 'Meta Error: ' . $errorMsg, type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
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

    public function getHealthStatus($template)
    {
        // Simple optimization: If REJECTED/FLAGGED, it's CRITICAL.
        if (in_array($template->status, ['REJECTED', 'FLAGGED', 'DISABLED']))
            return 'CRITICAL';
        if ($template->is_paused)
            return 'WARNING';

        // OPTIMIZATION: Use stored score if available (populated by Sync or Create)
        // If readiness_score exists and is > 0, trust it.
        // If validation_results is not null, trust it.
        if ($template->readiness_score !== null && $template->readiness_score < 100) {
            // If score < 100, checking severity of stored errors is faster than re-running regex
            $errors = $template->validation_results ?? [];
            foreach ($errors as $err) {
                if (($err['severity'] ?? '') === 'error')
                    return 'HIGH_RISK';
            }
            return 'WARNING';
        }

        // If no stored data (legacy), fallback to live calculation?
        // Or assume safe if approved.
        return 'SAFE';
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

<?php

namespace App\Livewire\Flows;

use App\Models\WhatsAppFlow;
use App\Services\WhatsAppFlowService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class FlowBuilder extends Component
{
    public $flowId;
    public $name;
    public $category = 'OTHER';
    public $usesDataEndpoint = true;
    public $screens = [];
    public $selectedScreenIndex = 0;
    public $selectedComponentIndex = null;
    public $after_submit_action = 'none';
    public $allowed_entry_points = ['template'];

    public $categories = [
        'SIGN_UP',
        'SIGN_IN',
        'APPOINTMENT_BOOKING',
        'LEAD_GENERATION',
        'CONTACT_US',
        'CUSTOMER_SUPPORT',
        'SURVEY',
        'OTHER'
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'screens' => 'required|array|min:1',
    ];

    public function mount($flowId = null)
    {
        if ($flowId) {
            $flow = WhatsAppFlow::where('team_id', Auth::user()->currentTeam->id)->findOrFail($flowId);
            $this->flowId = $flow->id;
            $this->name = $flow->name;
            $this->category = $flow->category ?? 'OTHER';
            $this->usesDataEndpoint = $flow->uses_data_endpoint ?? true;
            $this->after_submit_action = $flow->design_data['after_submit_action'] ?? 'none';
            $this->allowed_entry_points = $flow->entry_point_config['allowed_entry_points'] ?? ['template'];

            // Fix: Ensure screens is never empty array
            $loadedScreens = $flow->design_data['screens'] ?? [];
            if (empty($loadedScreens)) {
                $loadedScreens = $this->defaultScreens();
            }
            $this->screens = $loadedScreens;
        } else {
            $this->name = 'New WhatsApp Flow';
            $this->category = 'OTHER';
            $this->usesDataEndpoint = true;
            $this->after_submit_action = 'none';
            $this->screens = $this->defaultScreens();
        }
    }

    protected function defaultScreens()
    {
        return [
            [
                'id' => 'screen_welcome',
                'title' => 'Welcome Screen',
                'components' => [
                    ['type' => 'TextBody', 'text' => 'Welcome to our service! Please fill out the form below.'],
                    ['type' => 'TextInput', 'label' => 'Your Name', 'name' => 'user_name', 'required' => true],
                    ['type' => 'Footer', 'label' => 'Next', 'on_click_action' => 'next']
                ]
            ]
        ];
    }

    public function addScreen()
    {
        $id = 'screen_' . uniqid();
        $this->screens[] = [
            'id' => $id,
            'title' => 'New Screen',
            'components' => [
                ['type' => 'TextBody', 'text' => 'Enter your content here...'],
                ['type' => 'Footer', 'label' => 'Submit', 'on_click_action' => 'complete']
            ]
        ];
        $this->selectedScreenIndex = count($this->screens) - 1;
    }

    public function removeScreen($index)
    {
        if (count($this->screens) > 1) {
            unset($this->screens[$index]);
            $this->screens = array_values($this->screens);
            $this->selectedScreenIndex = max(0, $this->selectedScreenIndex - 1);
        }
    }

    public function addComponent($type)
    {
        // Guard: Ensure screens exist
        if (empty($this->screens)) {
            $this->screens = $this->defaultScreens();
            $this->selectedScreenIndex = 0;
        }

        // Guard: Ensure selected index is valid
        if (!isset($this->screens[$this->selectedScreenIndex])) {
            $this->selectedScreenIndex = 0;
            // Re-check 0
            if (!isset($this->screens[0])) {
                $this->screens = $this->defaultScreens();
            }
        }

        $component = ['type' => $type];

        switch ($type) {
            case 'TextBody':
                $component['text'] = 'New text content';
                break;
            case 'TextInput':
                $component['label'] = 'Label';
                $component['name'] = 'field_' . uniqid();
                $component['required'] = true;
                break;
            case 'Dropdown':
            case 'Select':
                $component['label'] = 'Select Option';
                $component['name'] = 'select_' . uniqid();
                $component['options'] = [['label' => 'Option 1', 'value' => '1']];
                break;
            case 'TextArea':
                $component['label'] = 'Description';
                $component['name'] = 'description_' . uniqid();
                $component['required'] = false;
                break;
            case 'CheckboxGroup':
                $component['label'] = 'Choose many';
                $component['name'] = 'cb_' . uniqid();
                $component['options'] = [['label' => 'Choice 1', 'value' => '1']];
                break;
            case 'RadioGroup':
                $component['label'] = 'Choose one';
                $component['name'] = 'rg_' . uniqid();
                $component['options'] = [['label' => 'Option 1', 'value' => '1']];
                break;
            case 'DateField':
                $component['label'] = 'Pick a date';
                $component['name'] = 'date_' . uniqid();
                break;
            case 'PhotoPicker':
                // Check if screen already has a media picker
                foreach ($this->screens[$this->selectedScreenIndex]['components'] as $c) {
                    if (in_array($c['type'], ['PhotoPicker', 'DocumentPicker'])) {
                        $this->addError('components', 'Only one media picker is allowed per screen.');
                        return; // Prevent addition
                    }
                }
                $component['label'] = 'Upload Photo';
                $component['name'] = 'photo_' . uniqid();
                $component['photo_source'] = 'camera,gallery';
                break;
            case 'DocumentPicker':
                foreach ($this->screens[$this->selectedScreenIndex]['components'] as $c) {
                    if (in_array($c['type'], ['PhotoPicker', 'DocumentPicker'])) {
                        $this->addError('components', 'Only one media picker is allowed per screen.');
                        return; // Prevent addition
                    }
                }
                $component['label'] = 'Upload File';
                $component['name'] = 'doc_' . uniqid();
                $component['allowed_types'] = ['application/pdf', 'image/jpeg', 'image/png'];
                break;
            case 'Image':
                // Check limit
                $count = 0;
                foreach ($this->screens[$this->selectedScreenIndex]['components'] as $c) {
                    if ($c['type'] === 'Image')
                        $count++;
                }
                if ($count >= 3) {
                    $this->addError('components', 'Max 3 images allowed per screen.');
                    return;
                }
                $component['src'] = 'https://via.placeholder.com/800x400';
                $component['height'] = 200;
                break;
        }

        // Insert before Footer if exists
        $footerIndex = null;
        if (isset($this->screens[$this->selectedScreenIndex]['components'])) {
            foreach ($this->screens[$this->selectedScreenIndex]['components'] as $key => $comp) {
                if (($comp['type'] ?? '') === 'Footer') {
                    $footerIndex = $key;
                    break;
                }
            }
        }

        if ($footerIndex !== null) {
            array_splice($this->screens[$this->selectedScreenIndex]['components'], $footerIndex, 0, [$component]);
        } else {
            $this->screens[$this->selectedScreenIndex]['components'][] = $component;
        }
    }

    public function removeComponent($index)
    {
        unset($this->screens[$this->selectedScreenIndex]['components'][$index]);
        $this->screens[$this->selectedScreenIndex]['components'] = array_values($this->screens[$this->selectedScreenIndex]['components']);
        $this->selectedComponentIndex = null;
    }

    public function addOption($screenIndex, $componentIndex)
    {
        $this->screens[$screenIndex]['components'][$componentIndex]['options'][] = [
            'label' => 'New Option',
            'value' => uniqid()
        ];
    }

    public function removeOption($screenIndex, $componentIndex, $optionIndex)
    {
        unset($this->screens[$screenIndex]['components'][$componentIndex]['options'][$optionIndex]);
        $this->screens[$screenIndex]['components'][$componentIndex]['options'] = array_values($this->screens[$screenIndex]['components'][$componentIndex]['options']);
    }

    public function save()
    {
        $this->validate();

        $data = [
            'team_id' => Auth::user()->currentTeam->id,
            'name' => $this->name,
            'category' => $this->category,
            'uses_data_endpoint' => $this->usesDataEndpoint,
            'design_data' => [
                'screens' => $this->screens,
                'after_submit_action' => $this->after_submit_action,
            ],
            'entry_point_config' => [
                'allowed_entry_points' => $this->allowed_entry_points
            ]
        ];

        if ($this->flowId) {
            $flow = WhatsAppFlow::where('team_id', Auth::user()->currentTeam->id)->findOrFail($this->flowId);
            $flow->update($data);
        } else {
            $flow = WhatsAppFlow::create($data);
            $this->flowId = $flow->id;
        }

        // Readiness Validation
        $validator = new \App\Validators\FlowReadinessValidator();
        $result = $validator->validate($flow);

        if (!$result->isValid()) {
            $warnings = implode(' ', array_map(fn($e) => $e->message, $result->getBlockingErrors()));
            session()->flash('warning', "Saved, but flow has issues: {$warnings}");
        } else {
            session()->flash('success', 'Flow design saved and Validated!');
        }
    }

    public function deploy()
    {
        $this->save();
        $flow = WhatsAppFlow::where('team_id', Auth::user()->currentTeam->id)->findOrFail($this->flowId);

        try {
            $service = new WhatsAppFlowService();
            $service->setTeam(Auth::user()->currentTeam);

            if (!$flow->flow_id) {
                $service->createFlowOnMeta($flow);
                $flow->refresh(); // Reload to get flow_id
            }

            // Readiness Check before Publish
            $validator = new \App\Validators\FlowReadinessValidator();
            $result = $validator->validate($flow);
            if (!$result->isValid()) {
                $reason = $result->getBlockingReason();
                session()->flash('error', "Cannot deploy: " . $reason);
                return;
            }

            $service->updateFlowDesign($flow, ['screens' => $this->screens]);

            // Publish the flow to make it live
            $service->publishFlow($flow);

            session()->flash('success', 'Flow deployed and published to Meta successfully!');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function restoreVersion($versionId)
    {
        // 1. Fetch Version
        $version = \App\Models\WhatsAppFlowVersion::where('whatsapp_flow_id', $this->flowId)->findOrFail($versionId);

        // 2. Load Design Data from Version
        if (!empty($version->design_data['screens'])) {
            $this->screens = $version->design_data['screens'];
            $this->after_submit_action = $version->design_data['after_submit_action'] ?? 'none';
        }

        // 3. Load Entry Point Config
        $this->allowed_entry_points = $version->entry_point_config['allowed_entry_points'] ?? ['template'];

        // 4. Save as new Draft
        $this->save();

        session()->flash('success', "Restored Version #{$version->version_number} as current Draft.");
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $versions = [];
        if ($this->flowId) {
            $versions = \App\Models\WhatsAppFlowVersion::where('whatsapp_flow_id', $this->flowId)
                ->orderBy('version_number', 'desc')
                ->get();
        }

        return view('livewire.flows.flow-builder', [
            'versions' => $versions
        ]);
    }
}

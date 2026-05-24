<?php

namespace App\Livewire\Campaigns\Wizard;

use Livewire\Component;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Computed;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Auth;

class MessageEditor extends Component
{
    #[Modelable]
    public $message = '';

    public $campaignType = 'one_off';
    public $selectedTemplateId = '';
    public $templateVars = [];
    public $headerMediaUrl = '';
    public $headerMediaFile = null;
    public $dripSteps = [];
    public $currentDripStep = 0;

    public $mainStep = [
        'template_id' => '',
        'variables' => [],
        'header_media_url' => '',
    ];

    public function mount($selectedTemplateId = '', $templateVars = [], $headerMediaUrl = '', $dripSteps = [], $campaignType = 'one_off')
    {
        $this->campaignType = $campaignType;
        $this->dripSteps = $dripSteps;
        $this->mainStep = [
            'template_id' => $selectedTemplateId,
            'variables' => $templateVars,
            'header_media_url' => $headerMediaUrl,
        ];
        $this->loadStepData(0);
    }

    public function updatedSelectedTemplateId($value)
    {
        $this->templateVars = [];
        $this->headerMediaFile = null;
        $this->headerMediaUrl = null;

        if ($value) {
            $template = WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)->find($value);
            if ($template) {
                // Initialize variables based on body params count
                $bodyText = '';
                foreach ($template->components ?? [] as $c) {
                    if (($c['type'] ?? '') === 'BODY') {
                        $bodyText = $c['text'] ?? '';
                    }
                }
                preg_match_all('/{{(\d+)}}/', $bodyText, $matches);
                $paramCount = count(array_unique($matches[1] ?? []));

                for ($i = 1; $i <= $paramCount; $i++) {
                    $this->templateVars[$i - 1] = '';
                }
            }
        }

        $this->saveCurrentStepData();
        $this->syncToParent();
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'selectedTemplateId') {
            return;
        }

        $this->saveCurrentStepData();
        $this->syncToParent();
    }

    public function addDripStep()
    {
        $this->saveCurrentStepData();

        $this->dripSteps[] = [
            'template_id' => null,
            'template_name' => '',
            'language' => 'en_US',
            'delay_minutes' => 1440, // 1 day
            'variables' => [],
            'header_params' => [],
        ];

        $this->currentDripStep = count($this->dripSteps);
        $this->loadStepData($this->currentDripStep);
        $this->syncToParent();
    }

    public function removeDripStep($index)
    {
        unset($this->dripSteps[$index]);
        $this->dripSteps = array_values($this->dripSteps);

        $this->currentDripStep = 0;
        $this->loadStepData(0);
        $this->syncToParent();
    }

    public function selectDripStep($index)
    {
        $this->saveCurrentStepData();
        $this->currentDripStep = $index;
        $this->loadStepData($index);
        $this->syncToParent();
    }

    protected function saveCurrentStepData()
    {
        if ($this->currentDripStep === 0) {
            $this->mainStep = [
                'template_id' => $this->selectedTemplateId,
                'variables' => $this->templateVars,
                'header_media_url' => $this->headerMediaUrl,
            ];
        } else {
            $stepIndex = $this->currentDripStep - 1;
            if (isset($this->dripSteps[$stepIndex])) {
                $this->dripSteps[$stepIndex]['template_id'] = $this->selectedTemplateId;
                $this->dripSteps[$stepIndex]['variables'] = $this->templateVars;
                
                $template = WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)->find($this->selectedTemplateId);
                $this->dripSteps[$stepIndex]['template_name'] = $template ? $template->name : '';
                $this->dripSteps[$stepIndex]['language'] = $template ? $template->language : 'en_US';
            }
        }
    }

    protected function loadStepData($index)
    {
        if ($index === 0) {
            $this->selectedTemplateId = $this->mainStep['template_id'] ?? '';
            $this->templateVars = $this->mainStep['variables'] ?? [];
            $this->headerMediaUrl = $this->mainStep['header_media_url'] ?? '';
        } else {
            $stepIndex = $index - 1;
            if (isset($this->dripSteps[$stepIndex])) {
                $this->selectedTemplateId = $this->dripSteps[$stepIndex]['template_id'] ?? '';
                $this->templateVars = $this->dripSteps[$stepIndex]['variables'] ?? [];
                $this->headerMediaUrl = ''; // Drip steps do not use media headers in this UI
            } else {
                $this->selectedTemplateId = '';
                $this->templateVars = [];
                $this->headerMediaUrl = '';
            }
        }
    }

    protected function syncToParent()
    {
        $mainId = $this->currentDripStep === 0 ? $this->selectedTemplateId : ($this->mainStep['template_id'] ?? '');
        $mainVars = $this->currentDripStep === 0 ? $this->templateVars : ($this->mainStep['variables'] ?? []);
        $mainMedia = $this->currentDripStep === 0 ? $this->headerMediaUrl : ($this->mainStep['header_media_url'] ?? '');

        $this->dispatch('messageEditorUpdated',
            selectedTemplateId: $mainId,
            templateVars: $mainVars,
            headerMediaUrl: $mainMedia,
            dripSteps: $this->dripSteps
        );
    }

    #[Computed]
    public function templates()
    {
        return WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)
            ->where('status', 'APPROVED')
            ->get();
    }

    #[Computed]
    public function templateInfo()
    {
        if (! $this->selectedTemplateId) {
            return null;
        }
        
        $template = WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)->find($this->selectedTemplateId);
        if (! $template) {
            return null;
        }

        $body = collect($template->components)->firstWhere('type', 'BODY');
        $header = collect($template->components)->firstWhere('type', 'HEADER');
        $footer = collect($template->components)->firstWhere('type', 'FOOTER');

        return [
            'name' => $template->name,
            'bodyText' => $body['text'] ?? '',
            'headerType' => $header['format'] ?? 'NONE',
            'headerText' => $header['text'] ?? '',
            'footerText' => $footer['text'] ?? '',
            'paramCount' => preg_match_all('/\{\{(\d+)\}\}/', ($body['text'] ?? '') . ($header['text'] ?? ''), $matches),
        ];
    }

    public function goToStep(int $step): void
    {
        $this->dispatch('campaignWizardGoToStep', step: $step);
    }

    public function render()
    {
        return view('livewire.campaigns.wizard.message-editor');
    }
}

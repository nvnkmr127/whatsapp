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

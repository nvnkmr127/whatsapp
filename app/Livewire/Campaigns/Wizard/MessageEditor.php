<?php

namespace App\Livewire\Campaigns\Wizard;

use Livewire\Component;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Computed;

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
        return \App\Models\Template::where('team_id', auth()->user()->currentTeam->id)
            ->where('status', 'approved')
            ->get();
    }

    #[Computed]
    public function templateInfo()
    {
        if (!$this->selectedTemplateId) return null;
        
        $template = \App\Models\Template::find($this->selectedTemplateId);
        if (!$template) return null;

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

    public function render()
    {
        return view('livewire.campaigns.wizard.message-editor');
    }
}

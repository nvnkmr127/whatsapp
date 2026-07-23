<?php

namespace App\Livewire\Chat;

use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TemplatePicker extends Component
{
    public $conversationId;

    public $showTemplateListModal = false;

    public $showTemplatePreviewModal = false;

    public $templateSearch = '';

    public $selectedTemplateId = null;

    public $templateVariables = [];

    protected $listeners = [
        'openTemplatePicker' => 'openTemplateList',
    ];

    public function openTemplateList()
    {
        $this->showTemplateListModal = true;
    }

    public function selectTemplate($id)
    {
        $this->selectedTemplateId = $id;
        $template = WhatsappTemplate::find($id);

        if (! $template) {
            return;
        }

        $this->templateVariables = [];
        foreach ($template->components ?? [] as $component) {
            if ($component['type'] === 'BODY') {
                preg_match_all('/{{(\d+)}}/', $component['text'], $matches);
                if (! empty($matches[1])) {
                    foreach ($matches[1] as $index) {
                        $this->templateVariables[$index] = '';
                    }
                }
            }
            if ($component['type'] === 'HEADER' && in_array($component['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                $this->templateVariables['header_media_url'] = '';
            }
        }

        $this->showTemplateListModal = false;
        $this->showTemplatePreviewModal = true;
    }

    public function closeTemplateModals()
    {
        $this->showTemplateListModal = false;
        $this->showTemplatePreviewModal = false;
        $this->reset(['selectedTemplateId', 'templateVariables', 'templateSearch']);
    }

    public function sendTemplate()
    {
        $template = WhatsappTemplate::find($this->selectedTemplateId);
        if (! $template) {
            return;
        }

        $this->dispatch('templateSelected', [
            'template_name' => $template->name,
            'language' => $template->language,
            'variables' => $this->templateVariables,
        ]);

        $this->closeTemplateModals();
    }

    public function getFilteredTemplatesProperty()
    {
        if (! Auth::check() || ! Auth::user()->current_team_id) {
            return collect();
        }

        return WhatsappTemplate::where('team_id', Auth::user()->current_team_id)
            ->where('status', 'APPROVED')
            ->when($this->templateSearch, function ($query) {
                $query->where('name', 'like', '%'.$this->templateSearch.'%');
            })
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.chat.template-picker', [
            // The list lives inside a modal that is closed on almost every render,
            // and openTemplateList() re-renders, so loading it eagerly buys nothing.
            'templates' => $this->showTemplateListModal ? $this->filtered_templates : collect(),
        ]);
    }
}

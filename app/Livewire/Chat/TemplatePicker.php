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
        if (! Auth::check() || ! Auth::user()->currentTeam) {
            return;
        }

        $template = WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)
            ->find($id);

        if (! $template) {
            return;
        }

        $this->selectedTemplateId = $template->id;
        $this->templateVariables = [];

        $components = $template->components ?? [];
        if (is_string($components)) {
            $components = json_decode($components, true) ?: [];
        }

        if (is_array($components)) {
            foreach ($components as $component) {
                if (! is_array($component)) {
                    continue;
                }

                $type = strtoupper($component['type'] ?? '');
                if ($type === 'BODY' && ! empty($component['text'])) {
                    preg_match_all('/{{(\d+)}}/', (string) $component['text'], $matches);
                    if (! empty($matches[1])) {
                        foreach ($matches[1] as $index) {
                            $this->templateVariables[(string) $index] = '';
                        }
                    }
                }
                if ($type === 'HEADER' && in_array(strtoupper($component['format'] ?? ''), ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                    $this->templateVariables['header_media_url'] = '';
                }
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
        $template = WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)
            ->find($this->selectedTemplateId);
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
        if (! Auth::check() || ! Auth::user()->currentTeam) {
            return collect();
        }

        return WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)
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

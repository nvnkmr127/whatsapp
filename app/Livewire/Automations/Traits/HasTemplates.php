<?php

namespace App\Livewire\Automations\Traits;

trait HasTemplates
{
    public bool $showTemplatesModal = false;
    public string $templateSearch = '';
    public string $selectedIndustry = 'All';
    public string $selectedUseCase = 'All';

    public function openTemplatesModal(): void
    {
        $this->showTemplatesModal = true;
        $this->templateSearch = '';
        $this->selectedIndustry = 'All';
        $this->selectedUseCase = 'All';
    }

    public function loadTemplate(string $key): void
    {
        $tpl = \App\Services\Automations\TemplateLibrary::getAll()[$key] ?? null;
        if (!$tpl) return;

        if ($this instanceof \App\Livewire\Automations\AutomationList) {
            session()->flash('success', "Template \"{$tpl['name']}\" selected. Start building!");
            $this->redirect(route('automations.builder', ['template' => $key]));
            return;
        }

        $this->nodes = $tpl['nodes'];
        $this->edges = $tpl['edges'];
        $this->name  = $tpl['name'];
        $this->showTemplatesModal = false;
        $this->pushHistory();
        $this->runValidation();
        session()->flash('success', "Template \"{$tpl['name']}\" loaded. Customize and publish!");
    }

    public function getFilteredTemplatesProperty(): array
    {
        $all = \App\Services\Automations\TemplateLibrary::getAll();
        
        return collect($all)->filter(function($tpl) {
            $matchesSearch = empty($this->templateSearch) || 
                             str_contains(strtolower($tpl['name']), strtolower($this->templateSearch)) ||
                             str_contains(strtolower($tpl['desc']), strtolower($this->templateSearch));
            
            $matchesIndustry = $this->selectedIndustry === 'All' || ($tpl['industry'] ?? '') === $this->selectedIndustry;
            $matchesUseCase = $this->selectedUseCase === 'All' || ($tpl['use_case'] ?? '') === $this->selectedUseCase;

            return $matchesSearch && $matchesIndustry && $matchesUseCase;
        })->toArray();
    }

    public function getFlowTemplates(): array
    {
        return \App\Services\Automations\TemplateLibrary::getAll();
    }
}

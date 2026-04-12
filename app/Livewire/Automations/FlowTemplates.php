<?php

namespace App\Livewire\Automations;

use Livewire\Component;
use App\Services\Automations\TemplateLibrary;
use Livewire\Attributes\Computed;

class FlowTemplates extends Component
{
    public $search = '';
    public $industry = 'All';
    public $useCase = 'All';

    #[Computed]
    public function filteredTemplates()
    {
        return collect(TemplateLibrary::getAll())
            ->filter(function ($tpl) {
                if ($this->industry !== 'All' && ($tpl['industry'] ?? '') !== $this->industry) return false;
                if ($this->useCase !== 'All' && ($tpl['use_case'] ?? '') !== $this->useCase) return false;
                if ($this->search && !str_contains(strtolower($tpl['name'].$tpl['desc']), strtolower($this->search))) return false;
                return true;
            });
    }

    public function selectTemplate($key)
    {
        $this->dispatch('template-selected', $key);
    }

    public function render()
    {
        return view('livewire.automations.flow-templates');
    }
}

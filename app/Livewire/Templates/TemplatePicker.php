<?php

namespace App\Livewire\Templates;

use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TemplatePicker extends Component
{
    // Props
    public $selectedTemplateId = null;
    public $allowedCategories = []; // Empty = All allowed
    public $minReadiness = 70;

    // Filters
    public $categoryFilter = '';
    public $languageFilter = '';
    public $showInactive = false;

    // Computed: Selected Template Context
    public $selectedTemplateReadiness = null;
    public $selectionWarning = null;

    public function mount($preselectedId = null, $allowedCategories = [], $minReadiness = 70)
    {
        $this->selectedTemplateId = $preselectedId;
        $this->allowedCategories = $allowedCategories;
        $this->minReadiness = $minReadiness;
    }

    public function updatedSelectedTemplateId($value)
    {
        $this->validateSelection($value);
        $this->dispatch('template-selected', id: $value);
    }

    public function validateSelection($id)
    {
        if (!$id) {
            $this->selectedTemplateReadiness = null;
            $this->selectionWarning = null;
            return;
        }

        $tpl = WhatsappTemplate::find($id);
        if (!$tpl)
            return;

        $this->selectedTemplateReadiness = $tpl->readiness_score;

        // Warning Logic
        if ($tpl->readiness_score < 70) {
            $this->selectionWarning = "High Risk: This template has a low quality score ({$tpl->readiness_score}). Delivery may fail.";
        } elseif ($tpl->readiness_score < 90) {
            $this->selectionWarning = "Caution: This template has some issues ({$tpl->readiness_score}/100).";
        } else {
            $this->selectionWarning = null;
        }
    }

    public function getTemplatesProperty()
    {
        $query = WhatsappTemplate::query()
            ->where('team_id', Auth::user()->currentTeam->id);

        // Security Scope (unless showing inactive explicitly)
        if (!$this->showInactive) {
            $query->safeForSending();
        }

        // Category Filter (Hard constraint + User filter)
        if (!empty($this->allowedCategories)) {
            $query->whereIn('category', $this->allowedCategories);
        }

        if ($this->categoryFilter) {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->languageFilter) {
            $query->where('language', $this->languageFilter);
        }

        return $query->latest()->get();
    }

    public function render()
    {
        return view('livewire.templates.template-picker', [
            'templates' => $this->templates
        ]);
    }
}

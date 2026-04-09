<?php

namespace App\Livewire\Automations;

use App\Models\MessageBot;
use App\Models\TemplateBot;
use App\Models\WhatsappTemplate;
use Livewire\Component;
use Livewire\WithFileUploads;

class AutomationCreator extends Component
{
    use WithFileUploads;

    public $type = 'keyword'; // 'keyword' or 'template' (from query param)

    public $bot_name;

    public $trigger_keywords = []; // Array of strings

    public $is_active = true;

    // Keyword Bot Specifics
    public $reply_text;

    public $reply_type = 1; // 1=text

    // Template Bot Specifics
    public $template_id; // Local template ID (or string if Meta ID used as FK)

    public $header_params = [];

    public $body_params = [];

    public $footer_params = [];

    public $selectedTemplate;

    public $headerParamCount = 0;

    public $bodyParamCount = 0;

    protected $rules = [
        'bot_name' => 'required|string|max:255',
        'trigger_keywords' => 'required|array|min:1',
        'trigger_keywords.*' => 'string|distinct|min:1',
    ];

    public function mount()
    {
        $this->type = request()->query('type', 'keyword');
    }

    public function updatedTemplateId($value)
    {
        $this->selectedTemplate = WhatsappTemplate::where('team_id', auth()->user()->currentTeam->id)
            ->where('whatsapp_template_id', $value)
            ->first();
        $this->header_params = [];
        $this->body_params = [];
        $this->footer_params = [];
        $this->headerParamCount = $this->getTemplateVariableCount($this->selectedTemplate, 'HEADER');
        $this->bodyParamCount = $this->getTemplateVariableCount($this->selectedTemplate, 'BODY');
    }

    // Helper to add keyword
    public function addKeyword($keyword)
    {
        if ($keyword && ! in_array($keyword, $this->trigger_keywords)) {
            $this->trigger_keywords[] = $keyword;
        }
    }

    public function removeKeyword($index)
    {
        unset($this->trigger_keywords[$index]);
        $this->trigger_keywords = array_values($this->trigger_keywords);
    }

    public function save()
    {
        $this->validate();
        $teamId = auth()->user()->currentTeam->id;

        if ($this->type === 'keyword') {
            $this->validate([
                'reply_text' => 'required|string',
            ]);

            MessageBot::create([
                'team_id' => $teamId,
                'name' => $this->bot_name,
                'reply_text' => $this->reply_text,
                'trigger' => $this->trigger_keywords, // Cast handling handles array
                'is_bot_active' => $this->is_active,
                'reply_type' => 1, // Text only for MVP
            ]);

        } elseif ($this->type === 'template') {
            $this->validate([
                'template_id' => 'required',
            ]);

            $templateIdentifier = $this->selectedTemplate?->whatsapp_template_id ?: $this->template_id;

            TemplateBot::create([
                'team_id' => $teamId,
                'name' => $this->bot_name,
                'template_id' => $templateIdentifier,
                'whatsapp_template_id' => $templateIdentifier,
                'trigger' => $this->trigger_keywords,
                'is_bot_active' => $this->is_active,
                'header_params' => $this->header_params,
                'body_params' => $this->body_params,
                'footer_params' => $this->footer_params,
            ]);
        }

        return redirect()->route('automations.index', ['activeTab' => $this->type]);
    }

    public function render()
    {
        $templates = WhatsappTemplate::where('team_id', auth()->user()->currentTeam->id)
            ->where('status', 'APPROVED')
            ->whereNotNull('whatsapp_template_id')
            ->orderBy('name')
            ->get();

        return view('livewire.automations.automation-creator', [
            'templates' => $templates,
        ]);
    }

    protected function getTemplateVariableCount(?WhatsappTemplate $template, string $componentType): int
    {
        if (! $template) {
            return 0;
        }

        $components = $template->components ?? [];
        $variables = [];

        foreach ($components as $component) {
            if (($component['type'] ?? null) !== $componentType) {
                continue;
            }
            $text = $component['text'] ?? '';
            if ($text && preg_match_all('/\{\{(\d+)\}\}/', $text, $matches)) {
                $variables = array_merge($variables, $matches[1]);
            }
        }

        $variables = array_unique($variables);

        return count($variables);
    }
}

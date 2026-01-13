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
        $this->selectedTemplate = WhatsappTemplate::where('template_id', $value)->first();
        $this->header_params = [];
        $this->body_params = [];
        $this->footer_params = [];
    }

    // Helper to add keyword
    public function addKeyword($keyword)
    {
        if ($keyword && !in_array($keyword, $this->trigger_keywords)) {
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

        if ($this->type === 'keyword') {
            $this->validate([
                'reply_text' => 'required|string',
            ]);

            MessageBot::create([
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

            TemplateBot::create([
                'name' => $this->bot_name,
                'template_id' => $this->template_id,
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
        $templates = WhatsappTemplate::where('status', 'APPROVED')->get();
        return view('livewire.automations.automation-creator', [
            'templates' => $templates
        ]);
    }
}

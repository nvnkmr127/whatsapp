<?php

namespace App\Livewire\Admin\EmailTemplates;

use App\Enums\EmailUseCase;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Livewire\Component;

class Create extends Component
{
    // Form fields
    public $slug;
    public $name;
    public $type;
    public $subject;
    public $content_html = '';
    public $content_text = '';
    public $description;
    public $variable_schema = []; // Array of strings
    public $variable_schema_input = ''; // Comma separated string for input

    // Validation rules
    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:email_templates,slug',
            'type' => 'required|string',
            'subject' => 'required|string|max:255',
            'content_html' => 'required|string',
            'content_text' => 'nullable|string',
            'description' => 'nullable|string',
            'variable_schema_input' => 'nullable|string',
        ];
    }

    public function mount()
    {
        // Default to first type if available
        $this->type = EmailUseCase::cases()[0]->value ?? '';
    }

    public function store()
    {
        $this->validate();

        // Process schema
        $schema = array_map('trim', explode(',', $this->variable_schema_input));
        $schema = array_filter($schema); // Remove empty values

        // Validate content against schema
        $service = app(EmailTemplateService::class);
        try {
            $service->validateTemplateContent($this->content_html, $schema);
        } catch (\Exception $e) {
            $this->addError('content_html', $e->getMessage());
            return;
        }

        EmailTemplate::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type, // Cast handled by model
            'subject' => $this->subject,
            'content_html' => $this->content_html,
            'content_text' => $this->content_text,
            'description' => $this->description,
            'variable_schema' => $schema,
            'is_locked' => false,
            'is_active' => true,
        ]);

        session()->flash('success', 'Template created successfully.');
        return redirect()->route('admin.email-templates.index');
    }

    public function render()
    {
        return view('livewire.admin.email-templates.create', [
            'types' => EmailUseCase::cases(),
        ])->layout('layouts.app');
    }
}

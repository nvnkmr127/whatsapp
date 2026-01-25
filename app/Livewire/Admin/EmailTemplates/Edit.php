<?php

namespace App\Livewire\Admin\EmailTemplates;

use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Livewire\Component;

class Edit extends Component
{
    public EmailTemplate $template;

    // Form fields
    public $slug;
    public $name;
    public $subject;
    public $content_html;
    public $content_text;
    public $description;

    // Preview
    public $previewHtml;
    public $previewSubject;
    public $previewData;
    public $activeTab = 'edit'; // edit, preview

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content_html' => 'required|string',
            'content_text' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    }

    public function mount(EmailTemplate $template)
    {
        $this->template = $template;
        $this->slug = $template->slug;
        $this->name = $template->name;
        $this->subject = $template->subject;
        $this->content_html = $template->content_html;
        $this->content_text = $template->content_text;
        $this->description = $template->description;
    }

    public function update()
    {
        $this->validate();

        // Additional validation for variables
        $service = app(EmailTemplateService::class);
        try {
            $service->validateTemplateContent($this->content_html, $this->template->variable_schema ?? []);
        } catch (\Exception $e) {
            $this->addError('content_html', $e->getMessage());
            return;
        }

        $this->template->update([
            'name' => $this->template->is_locked ? $this->template->name : $this->name, // Name might be lockable too depending on needs, simplifying
            'subject' => $this->subject,
            'content_html' => $this->content_html,
            'content_text' => $this->content_text,
            'description' => $this->description,
        ]);

        session()->flash('success', 'Template updated successfully.');
    }

    public function loadPreview()
    {
        $service = app(EmailTemplateService::class);

        // Temporarily update model in memory to preview current changes
        $this->template->subject = $this->subject;
        $this->template->content_html = $this->content_html;
        $this->template->content_text = $this->content_text;

        $preview = $service->preview($this->template);

        $this->previewSubject = $preview['subject'];
        $this->previewHtml = $preview['html'];
        $this->previewData = $preview['data'];
        $this->activeTab = 'preview';
    }

    public function render()
    {
        return view('livewire.admin.email-templates.edit')->layout('layouts.app');
    }
}

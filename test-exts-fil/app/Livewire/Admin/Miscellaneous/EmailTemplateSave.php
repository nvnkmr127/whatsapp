<?php

namespace App\Livewire\Admin\Miscellaneous;

use App\Models\EmailTemplate;
use App\Services\MergeFields;
use Livewire\Attributes\Rule;
use Livewire\Component;

class EmailTemplateSave extends Component
{
    public EmailTemplate $emailTemplate;

    public $templateId;

    #[Rule('required|string|max:255')]
    public $name;

    #[Rule('required|string|max:255')]
    public $subject;

    #[Rule('required|string')]
    public $message;

    public $userFields = [];

    public $contactFields = [];

    public $otherFields = [];

    public $selectedGroups = [];

    public $groupedFields = [];

    public function mount($id = null)
    {
        if (! checkPermission('email_template.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->emailTemplate = $id ? EmailTemplate::find($id) : new EmailTemplate;

        if ($this->emailTemplate->exists) {
            $this->templateId = $id;
            $this->loadTemplateData();
        }

        $this->loadMergeFields();
    }

    public function loadTemplateData()
    {
        $template = EmailTemplate::find($this->templateId);

        if ($template) {
            $this->name           = $template->name;
            $this->subject        = $template->subject;
            $this->message        = $template->message;
            $this->selectedGroups = $template->merge_fields_groups ?? [];
        }
    }

    public function loadMergeFields()
    {
        $mergeFieldsService = app(MergeFields::class);

        $slug = $this->emailTemplate->slug ?? null;

        if ($slug) {
            $this->groupedFields = $mergeFieldsService->getGroupedFieldsByTemplateSlug($slug);
        }
    }

    public function insertMergeField($field)
    {
        $this->message .= ' ' . $field;

        // Find which group contains this field
        foreach ($this->groupedFields as $group => $fields) {
            if (collect($fields)->contains('key', $field)) {
                if (! in_array($group, $this->selectedGroups)) {
                    $this->selectedGroups[] = $group;
                }
                break;
            }
        }
    }

    protected function determineFieldGroup($field)
    {
        // Determine which group the field belongs to
        if ($this->fieldExistsInGroup($field, $this->userFields)) {
            return 'user-group';
        }
        if ($this->fieldExistsInGroup($field, $this->contactFields)) {
            return 'contact-group';
        }
        if ($this->fieldExistsInGroup($field, $this->otherFields)) {
            return 'other-group';
        }

        return null;
    }

    protected function fieldExistsInGroup($field, $groupFields)
    {
        return collect($groupFields)->contains('key', $field);
    }

    public function cancel()
    {
        return to_route('admin.emails');
    }

    public function save()
    {
        $this->validate();

        try {

            $template          = EmailTemplate::find($this->templateId);
            $template->subject = $this->subject;
            $template->message = $this->message;

            $template->save();

            $this->notify(['type' => 'success', 'message' => t('email_template_updated_successfully')], true);

            return $this->redirect(route('admin.emails'));
        } catch (\Exception $e) {
            app_log('Failed to update email template: ' . $e->getMessage(), 'error', $e, [
                'template_id' => $this->templateId,
            ]);

            $this->notify(['type' => 'danger', 'message' => t('email_template_update_failed')]);
        }
    }

    public function render()
    {
        return view('livewire.admin.miscellaneous.email-template-save');
    }
}

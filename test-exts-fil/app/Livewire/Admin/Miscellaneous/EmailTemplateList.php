<?php

namespace App\Livewire\Admin\Miscellaneous;

use App\Models\EmailTemplate;
use Livewire\Component;

class EmailTemplateList extends Component
{
    public $templates;

    public function mount()
    {
        if (! checkPermission('email_template.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->templates = EmailTemplate::where('slug', '!=', 'smtp-test-mail')->get();
    }

    public function toggleActive($templateId, $activateTemplate)
    {
        $template = EmailTemplate::find($templateId);

        if ($template) {
            $template->update([
                'is_active' => $activateTemplate,
            ]);
        }

        $this->notify(['type' => 'success', 'message' => $activateTemplate ? t('template_activate_successfully') : t('template_deactivate_successfully')]);

        $this->templates = EmailTemplate::where('slug', '!=', 'smtp-test-mail')->get();
    }

    public function render()
    {
        return view('livewire.admin.miscellaneous.email-template-list');
    }
}

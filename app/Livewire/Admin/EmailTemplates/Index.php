<?php

namespace App\Livewire\Admin\EmailTemplates;

use App\Models\EmailTemplate;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.email-templates.index', [
            'templates' => EmailTemplate::orderBy('is_locked', 'desc')
                ->orderBy('name')
                ->get()
        ])->layout('layouts.app');
    }

    public function delete(EmailTemplate $template)
    {
        if ($template->is_locked) {
            session()->flash('error', 'Cannot delete a locked system template.');
            return;
        }

        $template->delete();
        session()->flash('success', 'Template deleted successfully.');
    }
}

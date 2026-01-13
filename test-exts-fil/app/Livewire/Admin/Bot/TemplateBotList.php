<?php

namespace App\Livewire\Admin\Bot;

use App\Models\TemplateBot;
use Livewire\Component;

class TemplateBotList extends Component
{
    public $confirmingDeletion = false;

    public $templatebotId = null;

    protected $listeners = [
        'confirmDelete' => 'confirmDelete',
    ];

    public function mount()
    {
        if (! checkPermission('template_bot.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
    }

    public function confirmDelete($templatebotId)
    {
        $this->templatebotId      = $templatebotId;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (checkPermission('template_bot.delete')) {
            TemplateBot::findOrFail($this->templatebotId)->delete();
            $this->confirmingDeletion = false;
            $this->notify(['type' => 'success', 'message' => t('template_bot_delete_successfully')]);
            $this->dispatch('pg:eventRefresh-template-bot-table-dgvpzs-table');
        }
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-template-bot-table-dgvpzs-table');
    }

    public function render()
    {
        return view('livewire.admin.bot.template-bot-list');
    }
}

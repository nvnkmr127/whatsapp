<?php

namespace App\Livewire\Admin\Bot;

use App\Models\MessageBots;
use Livewire\Component;

class MessageBotList extends Component
{
    public $confirmingDeletion = false;

    public $botId = null;

    protected $listeners = [
        'confirmDelete' => 'confirmDelete',
    ];

    public function mount()
    {
        if (! checkPermission('message_bot.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
    }

    public function confirmDelete($botId)
    {
        $this->botId              = $botId;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (checkPermission('message_bot.delete')) {
            $messageBot = MessageBots::findOrFail($this->botId);
            $files      = storage_path('/app/public/' . $messageBot->filename);
            if (is_file($files)) {
                unlink($files);
            }
            $messageBot->delete();
            $this->confirmingDeletion = false;
            $this->notify(['type' => 'success', 'message' => t('delete_message_bot_successfully')]);
            $this->dispatch('pg:eventRefresh-message-bot-table-73r5bi-table');
        }
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-message-bot-table-73r5bi-table');
    }

    public function render()
    {
        return view('livewire.admin.bot.message-bot-list');
    }
}

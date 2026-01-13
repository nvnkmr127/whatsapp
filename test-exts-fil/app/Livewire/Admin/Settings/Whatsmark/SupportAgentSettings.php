<?php

namespace App\Livewire\Admin\Settings\Whatsmark;

use Livewire\Component;

class SupportAgentSettings extends Component
{
    public ?bool $only_agents_can_chat = false;

    protected function rules()
    {
        return [
            'only_agents_can_chat' => 'nullable|boolean',
        ];
    }

    public function mount()
    {
        if (! checkPermission('whatsmark_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->loadSettings();
    }

    private function loadSettings()
    {
        $this->only_agents_can_chat = get_setting('whats-mark.only_agents_can_chat', false);
    }

    public function save()
    {
        if (checkPermission('whatsmark_settings.edit')) {
            $this->validate();

            $originalOnlyAgentsCanChat = get_setting('whats-mark.only_agents_can_chat', false);

            $modifiedSettings = [];

            if ($originalOnlyAgentsCanChat !== $this->only_agents_can_chat) {
                $modifiedSettings['only_agents_can_chat'] = $this->only_agents_can_chat;
            }

            // Save modified settings if any changes
            if (! empty($modifiedSettings)) {
                set_settings_batch('whats-mark', $modifiedSettings);
                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.whatsmark.support-agent-settings');
    }
}

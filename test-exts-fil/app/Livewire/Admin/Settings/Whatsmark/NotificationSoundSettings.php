<?php

namespace App\Livewire\Admin\Settings\Whatsmark;

use Livewire\Component;

class NotificationSoundSettings extends Component
{
    public ?bool $enable_chat_notification_sound = false;

    protected function rules()
    {
        return [
            'enable_chat_notification_sound' => 'nullable|boolean',
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
        $this->enable_chat_notification_sound = get_setting('whats-mark.enable_chat_notification_sound', false);
    }

    public function save()
    {
        if (checkPermission('whatsmark_settings.edit')) {
            $this->validate();

            $originalSetting = get_setting('whats-mark.enable_chat_notification_sound', false);

            $newSetting = $this->enable_chat_notification_sound;

            // Compare original setting with new setting, only save if modified
            if ($originalSetting !== $newSetting) {
                set_setting('whats-mark.enable_chat_notification_sound', $newSetting);
                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.whatsmark.notification-sound-settings');
    }
}

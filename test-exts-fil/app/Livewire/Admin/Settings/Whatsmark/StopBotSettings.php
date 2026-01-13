<?php

namespace App\Livewire\Admin\Settings\Whatsmark;

use App\Rules\PurifiedInput;
use Livewire\Component;

class StopBotSettings extends Component
{
    public $stop_bots_keyword = [];

    public $restart_bots_after = null;

    protected function rules()
    {
        return [
            'stop_bots_keyword'  => ['required', 'array', 'max:255', new PurifiedInput(t('sql_injection_error'))],
            'restart_bots_after' => 'nullable|numeric|min:0',
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

    protected function loadSettings()
    {
        $settings = get_settings_by_group('whats-mark');

        $this->stop_bots_keyword  = $settings->stop_bots_keyword ?? [];
        $this->restart_bots_after = $settings->restart_bots_after;
    }

    public function save()
    {
        if (checkPermission('whatsmark_settings.edit')) {
            $this->validate();

            $originalStopBotsKeyword  = get_setting('whats-mark.stop_bots_keyword', []);
            $originalRestartBotsAfter = get_setting('whats-mark.restart_bots_after', null);

            $newStopBotsKeyword  = $this->stop_bots_keyword;
            $newRestartBotsAfter = $this->restart_bots_after;

            $modifiedSettings = [];

            // Compare stop_bots_keyword
            if ($originalStopBotsKeyword !== $newStopBotsKeyword) {
                $modifiedSettings['stop_bots_keyword'] = $newStopBotsKeyword;
            }

            // Compare restart_bots_after
            if ($originalRestartBotsAfter !== $newRestartBotsAfter) {
                $modifiedSettings['restart_bots_after'] = $newRestartBotsAfter;
            }

            // Save modified settings
            if (! empty($modifiedSettings)) {
                set_settings_batch('whats-mark', $modifiedSettings);
                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.whatsmark.stop-bot-settings');
    }
}

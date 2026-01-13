<?php

namespace App\Livewire\Automations;

use Livewire\Component;

class StopAutomationSettings extends Component
{
    public $stop_bots_keyword = [];
    public $restart_bots_after = null;

    protected $rules = [
        'stop_bots_keyword' => ['required', 'array'],
        'restart_bots_after' => 'nullable|numeric|min:0',
    ];

    public function mount()
    {
        $this->loadSettings();
    }

    protected function loadSettings()
    {
        $this->stop_bots_keyword = get_setting('stop_bots_keyword', []);
        $this->restart_bots_after = get_setting('restart_bots_after');
    }

    public function save()
    {
        $this->validate();

        $settings = [
            'stop_bots_keyword' => $this->stop_bots_keyword,
            'restart_bots_after' => $this->restart_bots_after
        ];

        foreach ($settings as $key => $value) {
            set_setting($key, $value);
        }

        $this->dispatch('notify', 'Settings saved successfully');
    }

    public function render()
    {
        return view('livewire.automations.stop-automation-settings');
    }
}

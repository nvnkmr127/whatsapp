<?php

namespace App\Livewire\Admin\Settings\Whatsmark;

use App\Rules\PurifiedInput;
use Livewire\Component;

class WebHooksSettings extends Component
{
    public ?bool $enable_webhook_resend = false;

    public ?string $webhook_resend_method = '';

    public ?string $whatsapp_data_resend_to = '';

    public ?bool $only_agents_can_chat = false;

    public $id;

    protected function rules()
    {
        return [
            'enable_webhook_resend' => 'nullable|boolean',

            'webhook_resend_method' => [
                'nullable',
                'string',
                'max:255',
                new PurifiedInput(t('sql_injection_error')),
                'required_if:enable_webhook_resend,true',
            ],

            'whatsapp_data_resend_to' => [
                'nullable',
                'url',
                'max:255',
                new PurifiedInput(t('sql_injection_error')),
                'required_if:enable_webhook_resend,true',
            ],

            'only_agents_can_chat' => 'nullable|boolean',
        ];
    }

    public function mount()
    {
        if (! checkPermission('whatsmark_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->id = $this->getId();
        $this->loadSettings();
    }

    protected function loadSettings()
    {
        $settings = get_settings_by_group('whats-mark');

        $this->enable_webhook_resend   = $settings->enable_webhook_resend ?? false;
        $this->only_agents_can_chat    = $settings->only_agents_can_chat  ?? false;
        $this->webhook_resend_method   = $settings->webhook_resend_method;
        $this->whatsapp_data_resend_to = $settings->whatsapp_data_resend_to;
    }

    public function save()
    {
        if (checkPermission('whatsmark_settings.edit')) {
            $this->validate();

            $originalSettings = [
                'enable_webhook_resend'   => get_setting('whats-mark.enable_webhook_resend', false),
                'only_agents_can_chat'    => get_setting('whats-mark.only_agents_can_chat', false),
                'webhook_resend_method'   => get_setting('whats-mark.webhook_resend_method'),
                'whatsapp_data_resend_to' => get_setting('whats-mark.whatsapp_data_resend_to'),
            ];

            $modifiedSettings = [];

            // Iterate through each setting and check if it's modified
            foreach ($originalSettings as $key => $originalValue) {
                $newValue = $this->{$key};

                // If the value has changed, add it to the modified settings array
                if ($originalValue !== $newValue) {
                    $modifiedSettings[$key] = $newValue;
                }
            }

            // If there are any modified settings, save them
            if (! empty($modifiedSettings)) {
                set_settings_batch('whats-mark', $modifiedSettings);
                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.whatsmark.web-hooks-settings');
    }
}

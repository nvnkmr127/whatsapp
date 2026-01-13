<?php

namespace App\Livewire\Admin\Settings\System;

use Livewire\Component;

class WebhookSettingsManager extends Component
{
    public bool $webhook_enabled = false;

    public string $webhook_url = '';

    public array $contacts_actions = [];

    public array $status_actions = [];

    public array $source_actions = [];

    public function mount()
    {
        if (! checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $settings = get_settings_by_group('webhook');

        $this->webhook_enabled  = $settings->webhook_enabled  ?? false;
        $this->webhook_url      = $settings->webhook_url      ?? '';
        $this->contacts_actions = $settings->contacts_actions ?? [];
        $this->status_actions   = $settings->status_actions   ?? [];
        $this->source_actions   = $settings->source_actions   ?? [];
    }

    protected function rules()
    {
        return [
            'webhook_enabled'  => 'boolean',
            'webhook_url'      => 'required_if:webhook_enabled,true|url',
            'contacts_actions' => 'array',
            'status_actions'   => 'array',
            'source_actions'   => 'array',
        ];
    }

    public function save()
    {
        if (checkPermission('system_settings.edit')) {
            $this->validate();

            $originalSettings = get_settings_by_group('webhook');

            $newSettings = [
                'webhook_enabled'  => $this->webhook_enabled,
                'webhook_url'      => $this->webhook_url,
                'contacts_actions' => $this->contacts_actions,
                'status_actions'   => $this->status_actions,
                'source_actions'   => $this->source_actions,
            ];

            // Filter the settings that have been modified
            $modifiedSettings = array_filter($newSettings, function ($value, $key) use ($originalSettings) {
                return $originalSettings->$key !== $value;
            }, ARRAY_FILTER_USE_BOTH);

            // Save only if there are modifications
            if (! empty($modifiedSettings)) {
                set_settings_batch('webhook', $modifiedSettings);

                $this->notify([
                    'type'    => 'success',
                    'message' => 'Webhook settings updated successfully.',
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.system.webhook-settings-manager');
    }
}

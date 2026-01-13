<?php

namespace App\Livewire\Admin\Settings\System;

use App\Rules\PurifiedInput;
use Livewire\Component;

class SeoSettings extends Component
{
    public ?string $meta_title = '';

    public ?string $meta_description = '';

    private array $keys = [
        'meta_title'       => null,
        'meta_description' => null,
    ];

    public function validateMetaDescription()
    {
        $this->validate([
            'meta_description' => ['required', 'string', new PurifiedInput(t('sql_injection_error'))],
        ]);
    }

    protected function rules()
    {
        return [
            'meta_title'       => ['nullable', 'string', new PurifiedInput(t('sql_injection_error'))],
            'meta_description' => ['nullable', 'string', new PurifiedInput(t('sql_injection_error'))],
        ];
    }

    public function mount()
    {
        if (! checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->loadSettings();
    }

    protected function loadSettings()
    {
        $settings = get_settings_by_group('seo');

        $this->meta_title       = $settings->meta_title ?? false;
        $this->meta_description = $settings->meta_description;
    }

    public function save()
    {
        if (checkPermission('system_settings.edit')) {
            $this->validate();

            $originalSettings = get_settings_by_group('seo');

            $newSettings = [
                'meta_title'       => $this->meta_title,
                'meta_description' => $this->meta_description,
            ];

            // Filter the settings that have been modified
            $modifiedSettings = array_filter($newSettings, function ($value, $key) use ($originalSettings) {
                return $originalSettings->$key !== $value;
            }, ARRAY_FILTER_USE_BOTH);

            // Save only if there are modifications
            if (! empty($modifiedSettings)) {
                set_settings_batch('seo', $modifiedSettings);
                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.system.seo-settings');
    }
}

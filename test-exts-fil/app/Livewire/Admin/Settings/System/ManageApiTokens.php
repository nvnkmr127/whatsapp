<?php

namespace App\Livewire\Admin\Settings\System;

use Illuminate\Support\Str;
use Livewire\Component;

class ManageApiTokens extends Component
{
    public bool $isEnabled = false;

    public ?string $currentToken = null;

    public bool $newTokenGenerated = false;

    public array $originalState = [];

    public function mount()
    {
        if (! checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->isEnabled    = (bool) get_setting('api.enabled', false);
        $this->currentToken = get_setting('api.token');

        // Store original state for isDirty check
        $this->originalState = [
            'isEnabled'    => $this->isEnabled,
            'currentToken' => $this->currentToken,
        ];
    }

    public function toggleApiAccess($value)
    {
        $this->isEnabled = (bool) $value;

        if ($this->isEnabled && empty($this->currentToken)) {
            $this->generateNewToken();
        }
    }

    public function generateNewToken()
    {
        $this->currentToken      = hash('sha256', Str::random(64));
        $this->newTokenGenerated = true;
    }

    public function isDirty(): bool
    {
        return $this->isEnabled !== $this->originalState['isEnabled'] || $this->currentToken !== $this->originalState['currentToken'];
    }

    public function save()
    {
        if (checkPermission('system_settings.edit')) {
            if (! $this->isDirty()) {
                return;
            }

            $updates = [
                'api.enabled' => $this->isEnabled,
                'api.token'   => $this->currentToken,
            ];

            if ($this->newTokenGenerated) {
                $updates['api.token_generated_at'] = now();
                $this->newTokenGenerated           = false;
            }

            foreach ($updates as $key => $value) {
                set_setting($key, $value);
            }

            // Update original state after saving
            $this->originalState = [
                'isEnabled'    => $this->isEnabled,
                'currentToken' => $this->currentToken,
            ];

            $this->notify([
                'type'    => 'success',
                'message' => t('api_setting_update_successfully'),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.system.manage-api-tokens', [
            'abilities' => config('api.abilities', []),
        ]);
    }
}

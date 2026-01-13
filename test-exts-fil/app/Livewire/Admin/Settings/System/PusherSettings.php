<?php

namespace App\Livewire\Admin\Settings\System;

use App\Rules\PurifiedInput;
use App\Services\PusherService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PusherSettings extends Component
{
    public ?string $app_id = null;

    public ?string $app_key = '';

    public ?string $app_secret = '';

    public ?string $cluster = '';

    public ?bool $real_time_notify = false;

    public ?bool $desk_notify = false;

    public $dismiss_desk_notification = 0;

    protected function rules()
    {
        return [
            'app_id'                    => ['nullable', 'string', new PurifiedInput(t('sql_injection_error'))],
            'app_key'                   => ['nullable', 'string', new PurifiedInput(t('sql_injection_error'))],
            'app_secret'                => ['nullable', 'string', new PurifiedInput(t('sql_injection_error'))],
            'cluster'                   => ['nullable', 'string', new PurifiedInput(t('sql_injection_error'))],
            'real_time_notify'          => ['nullable', 'boolean'],
            'desk_notify'               => ['nullable', 'boolean'],
            'dismiss_desk_notification' => ['nullable', 'integer', new PurifiedInput(t('sql_injection_error'))],
        ];
    }

    public function mount()
    {
        if (! checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $settings = get_settings_by_group('pusher');

        $this->app_id                    = $settings->app_id ?? false;
        $this->app_key                   = $settings->app_key;
        $this->app_secret                = $settings->app_secret;
        $this->cluster                   = $settings->cluster;
        $this->real_time_notify          = $settings->real_time_notify;
        $this->desk_notify               = $settings->desk_notify;
        $this->dismiss_desk_notification = $settings->dismiss_desk_notification;
    }

    public function save()
    {
        if (checkPermission('system_settings.edit')) {
            $this->validate();

            $originalSettings = get_settings_by_group('pusher');

            $newSettings = [
                'app_id'                    => $this->app_id,
                'app_key'                   => $this->app_key,
                'app_secret'                => $this->app_secret,
                'cluster'                   => $this->cluster,
                'real_time_notify'          => $this->real_time_notify,
                'desk_notify'               => $this->desk_notify,
                'dismiss_desk_notification' => $this->dismiss_desk_notification,
            ];

            // Filter the settings that have been modified
            $modifiedSettings = array_filter($newSettings, function ($value, $key) use ($originalSettings) {
                return $originalSettings->$key !== $value;
            }, ARRAY_FILTER_USE_BOTH);

            // Save only if there are modifications
            if (! empty($modifiedSettings)) {
                set_settings_batch('pusher', $modifiedSettings);
                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.system.pusher-settings');
    }

    public function testConnection(PusherService $pusherService)
    {
        if (! get_setting('pusher.app_key') || ! get_setting('pusher.app_secret') || ! get_setting('pusher.app_id')) {
            $this->notify([
                'type'    => 'danger',
                'message' => t('fill_required_pusher_credential'),
            ]);

            return;
        }

        if (! $pusherService->isPusherReady()) {
            $this->notify([
                'type'    => 'danger',
                'message' => t('pusher_is_not_initialized'),
            ]);

            return;
        }

        $user = Auth::user();

        try {
            $result = $pusherService->trigger('whatsmark-test-channel', 'whatsmark-test-event', [
                'title'       => '🌟 WhatsMark Notification Test',
                'message'     => t('hello') . ' ' . $user->firstname . ' ' . $user->lastname . ' ' . t('your_real_time_notification'),
                'autoDismiss' => $this->dismiss_desk_notification,
            ]);

            $this->notify([
                'type'    => $result['status'] ? 'success' : 'danger',
                'message' => $result['message'],
            ]);
        } catch (\Exception $e) {
            app_log(
                t('pusher_test_connection_error') . $e->getMessage(),
                'error',
                $e
            );

            $this->notify([
                'type'    => 'danger',
                'message' => t('pusher_test_connection_failed') . $e->getMessage(),
            ]);
        }
    }
}

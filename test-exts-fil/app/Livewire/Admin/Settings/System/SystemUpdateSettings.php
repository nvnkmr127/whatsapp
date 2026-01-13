<?php

namespace App\Livewire\Admin\Settings\System;

use Corbital\Installer\Classes\UpdateChecker;
use Livewire\Component;

class SystemUpdateSettings extends Component
{
    public $currentVersion;

    public $latestVersion;

    public $purchase_key;

    public $username;

    public $update_id;

    public $has_sql_update;

    public $releases = [];

    public $update = [];

    public $token;

    public $support = [];

    public $versionLog = [];

    protected $rules = [
        'purchase_key' => 'required|string',
        'username'     => 'required|string',
    ];

    public function mount()
    {
        $settings    = explode('|', get_setting('whats-mark.wm_verification_token'));
        $this->token = $settings[0];
        if (! checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->currentVersion = get_setting('whats-mark.wm_version');
        $this->loadReleases();
    }

    public function loadReleases()
    {
        try {
            $update_checker       = new UpdateChecker;
            $this->update         = $update_checker->checkUpdate($this->token, 'update')['data'];
            $this->support        = $update_checker->checkSupportExpiryStatus(get_setting('whats-mark.wm_support_until'));
            $this->latestVersion  = $this->update['latest_version'];
            $this->update_id      = $this->update['update_id'];
            $this->has_sql_update = $this->update['has_sql_update'];
            $this->versionLog     = $update_checker->getVersionLog();
        } catch (\Exception $e) {
            // Handle error case
            $this->notify(['type' => 'danger', 'message' => t('failed_to_load_release_data')]);
        }
    }

    public function save()
    {
        $this->validate();

        $update_checker = new UpdateChecker;
        $response       = $update_checker->downloadUpdate($this->update_id, $this->has_sql_update, $this->latestVersion, $this->token, $this->purchase_key, $this->username, 'update');
        $this->notify(['type' => ($response['success'] == true) ? 'success' : 'danger', 'message' => $response['message']]);

        if ($response['success'] == true) {
            set_setting('whats-mark.wm_version', $this->latestVersion);
            clear_view();

            return redirect(route('admin.system-update.settings.view'));
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.system.system-update-settings');
    }
}

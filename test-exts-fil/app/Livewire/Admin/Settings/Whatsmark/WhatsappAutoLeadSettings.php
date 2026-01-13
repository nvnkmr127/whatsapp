<?php

namespace App\Livewire\Admin\Settings\Whatsmark;

use App\Models\Source;
use App\Models\Status;
use App\Models\User;
use Livewire\Component;

class WhatsappAutoLeadSettings extends Component
{
    public ?bool $auto_lead_enabled = false;

    public $lead_status = null;

    public $lead_source = null;

    public $lead_assigned_to = null;

    public $id;

    protected function rules()
    {
        return [
            'auto_lead_enabled' => 'nullable|boolean',
            'lead_status'       => 'nullable|numeric|exists:statuses,id|required_if:auto_lead_enabled,true',
            'lead_source'       => 'nullable|numeric|exists:sources,id|required_if:auto_lead_enabled,true',
            'lead_assigned_to'  => 'nullable|numeric|exists:users,id',
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

        $this->auto_lead_enabled = $settings->auto_lead_enabled ?? false;
        $this->lead_status       = $settings->lead_status;
        $this->lead_source       = $settings->lead_source;
        $this->lead_assigned_to  = $settings->lead_assigned_to;
    }

    public function save()
    {
        if (checkPermission('whatsmark_settings.edit')) {
            $this->validate();

            $originalSettings = [
                'auto_lead_enabled' => get_setting('whats-mark.auto_lead_enabled', false),
                'lead_status'       => get_setting('whats-mark.lead_status'),
                'lead_source'       => get_setting('whats-mark.lead_source'),
                'lead_assigned_to'  => get_setting('whats-mark.lead_assigned_to'),
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

            if (! empty($modifiedSettings)) {
                set_settings_batch('whats-mark', $modifiedSettings);
                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.whatsmark.whatsapp-auto-lead-settings', [
            'statuses' => Status::select('id', 'name')->get(),
            'sources'  => Source::select('id', 'name')->get(),
            'users'    => User::select('id', 'firstname', 'lastname')->get(),
        ]);
    }
}

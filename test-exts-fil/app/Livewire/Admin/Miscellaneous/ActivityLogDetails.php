<?php

namespace App\Livewire\Admin\Miscellaneous;

use App\Models\WmActivityLog;
use Livewire\Component;

class ActivityLogDetails extends Component
{
    public $data;

    public function mount($logId)
    {
        if (! checkPermission('activity_log.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect()->route('admin.dashboard');
        }
        $this->data = WmActivityLog::find($logId);

    }

    public function render()
    {
        return view('livewire.admin.miscellaneous.activity-log-details', ['data' => $this->data]);
    }
}

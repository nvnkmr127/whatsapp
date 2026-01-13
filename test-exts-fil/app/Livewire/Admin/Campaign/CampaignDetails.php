<?php

namespace App\Livewire\Admin\Campaign;

use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\Contact;
use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use Livewire\Component;

class CampaignDetails extends Component
{
    public $campaign;

    public $template_name;

    public $totalDeliveredPercent;

    public $totalReadPercent;

    public $totalFailedPercent;

    public $totalContacts;

    public $totalCampaignsPercent;

    public $totalCount;

    public $status;

    public $campaignStatus;

    public $deliverCount;

    public $readCount;

    public $failedCount;

    public $isInQueue;

    public $isRetryAble;

    public function mount()
    {
        if (! checkPermission('campaigns.show_campaign')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect()->route('admin.dashboard');
        }
        $campaignId = request()->route('campaignId');

        $this->campaign              = Campaign::findOrFail($campaignId);
        $this->template_name         = WhatsappTemplate::select('template_name')->where('template_id', $this->campaign->template_id)->first()->template_name;
        $this->totalCount            = CampaignDetail::where('campaign_id', $campaignId)->count();
        $this->totalContacts         = Contact::where('type', $this->campaign->rel_type)->count();
        $this->totalCampaignsPercent = ! empty($this->totalContacts) ? round(($this->totalCount / $this->totalContacts) * 100, 2) : 0;
        $this->isRetryAble           = false;

        if ($this->totalCount > 0) {
            $this->deliverCount = CampaignDetail::where('campaign_id', $campaignId)->where('status', 2)->count();
            $this->readCount    = CampaignDetail::where('campaign_id', $campaignId)->where('message_status', 'read')->count();
            $this->failedCount  = CampaignDetail::where('campaign_id', $campaignId)->where('status', 0)->count();
            $this->isInQueue    = CampaignDetail::where('campaign_id', $campaignId)->where('status', 1)->exists();

            $scheduledTime = $this->campaign->scheduled_send_time;

            $givenTime     = Carbon::parse($scheduledTime, (! empty(get_setting('general.timezone'))) ? get_setting('general.timezone') : 'Asia/kolkata');
            $thresholdTime = $givenTime->copy()->addMinutes(5);
            $currentTime   = Carbon::now((! empty(get_setting('general.timezone'))) ? get_setting('general.timezone') : 'Asia/kolkata');

            if ($this->campaign->is_sent == true && ($this->failedCount > 0 || (! ($this->totalCount == $this->deliverCount) && $currentTime->gt($thresholdTime)))) {
                $this->isRetryAble = true;
            }
            $this->totalFailedPercent    = ! empty($this->totalCount) ? round(($this->failedCount / $this->totalCount) * 100, 2) : 0;
            $this->totalReadPercent      = ! empty($this->totalCount) ? round(($this->readCount / $this->totalCount) * 100, 2) : 0;
            $this->totalDeliveredPercent = ! empty($this->totalCount) ? round(($this->deliverCount / $this->totalCount) * 100, 2) : 0;

            // Determine campaign status
            if ($this->failedCount == $this->totalCount) {
                $this->campaignStatus = 'fail';
            } elseif ($this->deliverCount == $this->totalCount) {
                $this->campaignStatus = 'sent';
            } elseif (! $this->isInQueue) {
                $this->campaignStatus = 'executed';
            } else {
                $this->campaignStatus = 'pending';
            }
        } else {
            $this->totalFailedPercent    = 0;
            $this->totalReadPercent      = 0;
            $this->totalDeliveredPercent = 0;
            $this->campaignStatus        = 'Failed';
        }
    }

    public function resumeCampaign()
    {
        if (! $this->isInQueue) {
            return $this->notify(['type' => 'warning', 'message' => t('your_campaign_is_already_executed')]);
        }

        $newStatus = $this->campaign->pause_campaign ? 0 : 1;

        $this->campaign->update([
            'pause_campaign' => $newStatus,
        ]);

        $message = $newStatus ? t('campaign_paused_successfully') : t('campaign_resumed_successfully');

        $this->notify(['type' => 'success', 'message' => $message]);
    }

    public function retryCampaign()
    {
        if ($this->isRetryAble) {
            $campaign_id = $this->campaign->id;
            CampaignDetail::where('campaign_id', $campaign_id)
                ->where('status', 0)
                ->update(['status' => 1, 'message_status' => 'sent']);
            Campaign::where('id', $campaign_id)->update(['is_sent' => 0, 'scheduled_send_time' => now()]);
            $this->notify(['type' => 'success', 'message' => t('campaign_resend_process_initiated')]);
        } else {
            $this->notify(['type' => 'danger', 'message' => t('you_cant_resend_this_campaign')]);
        }
    }

    public function campaginList()
    {
        return redirect()->route('admin.campaigns.list');
    }

    public function createCampaign()
    {
        return redirect()->route('admin.campaigns.save');
    }

    public function render()
    {
        return view('livewire.admin.campaign.campaign-details');
    }
}

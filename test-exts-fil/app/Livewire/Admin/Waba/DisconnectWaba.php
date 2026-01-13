<?php

namespace App\Livewire\Admin\Waba;

use App\Models\WhatsappTemplate;
use App\Traits\WhatsApp;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DisconnectWaba extends Component
{
    use WhatsApp;

    public $token_info = [];

    public $phone_numbers = [];

    public $message_details = [];

    public $wm_test_message;

    public $confirmingDeletion = false;

    public function mount()
    {
        if (! checkPermission(['connect_account.view', 'connect_account.disconnect'])) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect()->route('admin.dashboard');
        }

        $phone_numbers       = $this->getPhoneNumbers();
        $this->phone_numbers = $phone_numbers['data'] ?? [];
        if ($phone_numbers['status']) {
            $webhook_configuration_url = array_column(array_column($phone_numbers['data'], 'webhook_configuration'), 'application');
            if (in_array(route('whatsapp.webhook'), $webhook_configuration_url)) {
                set_setting('whatsapp.is_webhook_connected', 1);
                set_setting('whatsapp.is_whatsmark_connected', 1);
            } else {
                set_setting('whatsapp.is_webhook_connected', 0);
                set_setting('whatsapp.is_whatsmark_connected', 0);
            }

            if (empty(get_setting('whatsapp.wm_default_phone_number_id')) || empty(get_setting('whatsapp.wm_default_phone_number'))) {
                $default_number    = preg_replace('/\D/', '', $this->phone_numbers[array_key_first($this->phone_numbers)]['display_phone_number']);
                $default_number_id = preg_replace('/\D/', '', $this->phone_numbers[array_key_first($this->phone_numbers)]['id']);
                set_setting('whatsapp.wm_default_phone_number', $default_number);
                set_setting('whatsapp.wm_default_phone_number_id', $default_number_id);
            }
            if (empty(get_setting('whatsapp.wm_health_data')) || empty(get_setting('whatsapp.wm_health_check_time'))) {
                $helthStatus = $this->getHealthStatus();
                set_setting('whatsapp.wm_health_check_time', date('l jS F Y g:i:s a'));
                set_setting('whatsapp.wm_health_data', json_encode($helthStatus['data']));
            }

            if (! empty(get_setting('whatsapp.wm_default_phone_number_id')) && ! empty(get_setting('whatsapp.wm_default_phone_number')) && ! file_exists(public_path('storage/images/qrcode.png'))) {
                @unlink(public_path('storage/images/qrcode.png'));
                $this->generateUrlQR('https://wa.me/' . get_setting('whatsapp.wm_default_phone_number'), true);
            }
        } else {
            set_setting('whatsapp.is_webhook_connected', 0);
            set_setting('whatsapp.is_whatsmark_connected', 0);
        }

        if (get_setting('whatsapp.is_whatsmark_connected') == 0 || get_setting('whatsapp.is_webhook_connected') == 0) {
            return redirect()->route('admin.connect');
        }

        $this->message_details = $this->getMessageLimit()['data'] ?? [];

        $token_info = $this->debugToken();
        if ($token_info['status']) {
            $this->token_info = $token_info['data'];
            if (isset($this->token_info['issued_at']) && ! empty($this->token_info['issued_at'])) {
                $epoch_time = $this->token_info['issued_at'];
                $dt         = new DateTime("@$epoch_time");
                $dt->setTimezone(new DateTimeZone((! empty(get_setting('general.timezone'))) ? get_setting('general.timezone') : 'Asia/kolkata'));
                $this->token_info['issued_at'] = $dt->format('l jS F Y g:i:s a');
            }
            $this->token_info['issued_at'] = $this->token_info['issued_at'] ?? '-';

            if (isset($this->token_info['expires_at']) && ! empty($this->token_info['expires_at'])) {
                $epoch_time = $this->token_info['expires_at'];
                $dt         = new DateTime("@$epoch_time");
                $dt->setTimezone(new DateTimeZone((! empty(get_setting('general.timezone'))) ? get_setting('general.timezone') : 'Asia/kolkata'));
                $data['expires_at'] = $dt->format('l jS F Y g:i:s a');
            }
            $this->token_info['expires_at'] = $this->token_info['expires_at'] ?? 'NA';
        }
    }

    public function refreshHealth()
    {
        // get profile url
        $data         = $this->getProfile();
        $profile_data = collect($data['data'])->firstWhere('messaging_product', 'whatsapp');
        set_setting('whatsapp.wm_profile_picture_url', $profile_data['profile_picture_url'] ?? '');

        if (! empty(get_setting('whatsapp.wm_default_phone_number_id')) && ! empty(get_setting('whatsapp.wm_default_phone_number')) && ! file_exists(public_path('storage/images/qrcode.png'))) {
            @unlink(public_path('storage/images/qrcode.png'));
            $this->generateUrlQR('https://wa.me/' . get_setting('whatsapp.wm_default_phone_number'), true);
        }

        // health data
        $response = $this->getHealthStatus();

        //subscribe webhook incase if it is not subscribe
        $this->subscribeWebhook();

        set_setting('whatsapp.wm_health_check_time', date('l jS F Y g:i:s a'));
        set_setting('whatsapp.wm_health_data', json_encode($response['data']));
        $this->notify(['message' => $response['message'] ?? t('health_status_updated'), 'type' => $response['status'] ? 'success' : 'danger']);
    }

    public function setDefaultNumber($wm_phone_number_id, $wm_default_phone_number)
    {
        set_setting('whatsapp.wm_default_phone_number_id', $wm_phone_number_id);
        $phone_number = preg_replace('/\D/', '', $wm_default_phone_number);
        set_setting('whatsapp.wm_default_phone_number', $phone_number);

        @unlink(public_path('storage/images/qrcode.png'));
        $this->generateUrlQR("https://wa.me/$phone_number", true);

        $this->notify(['message' => t('default_number_updated'), 'type' => 'success']);
    }

    public function sendTestMessage()
    {
        $this->validate([
            'wm_test_message' => [
                'required',
                'regex:/^\+[1-9]\d{9,14}$/',
            ],
        ], [
            'wm_test_message.required' => 'The mobile number field is required.',
            'wm_test_message.regex'    => 'The mobile number must be in the format +[country code][number] and contain at least 10 digits.',
        ]);
        $res = $this->sendTestMessages($this->wm_test_message);
        $this->notify(['status' => $res['status'] ?? false, 'message' => $res['message'] ?? t('something_went_wrong')]);
    }

    public function verifyWebhook()
    {

        $this->js(<<<'JS'
            this.processing = true;

            axios.post('/whatsapp/webhook', {
                message: 'ctl_whatsbot_ping',
                identifier: Date.now(),
                timestamp: new Date().toISOString()
            }, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                showNotification(response.data.message, response.data.status ? 'success' : 'danger');
            })
            .catch(error => {
                let errorMessage = error.response ? error.response.data.message : error.message;
                showNotification(errorMessage, 'danger');
            });
        JS);
    }

    public function disconnectAccount()
    {
        if (! checkPermission('connect_account.disconnect')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied')]);
            whatsapp_log('Unauthorized User trying to Disconnect Account', 'info', ['time' => time(), 'user' => Auth::user()->firstname . ' ' . Auth::user()->lastname, 'user-id' => Auth::id()]);
        }
        whatsapp_log('Account Disconnected', 'info', ['time' => time(), 'user' => Auth::user()->firstname . ' ' . Auth::user()->lastname, 'user-id' => Auth::id()]);

        @unlink(public_path('storage/images/qrcode.png'));

        $this->disconnectWebhook();

        $whatsappSettings = [
            'wm_fb_app_id'               => '',
            'wm_fb_app_secret'           => '',
            'wm_business_account_id'     => '',
            'wm_access_token'            => '',
            'is_webhook_connected'       => '0',
            'is_whatsmark_connected'     => '0',
            'wm_default_phone_number'    => '',
            'wm_default_phone_number_id' => '',
            'wm_health_check_time'       => '',
            'wm_health_data'             => '',
            'wm_profile_picture_url'     => '',
        ];

        set_settings_batch('whatsapp', $whatsappSettings);

        WhatsappTemplate::query()->truncate();

        $this->confirmingDeletion = false;

        $this->notify(['type' => 'danger', 'message' => t('account_disconnected')], true);

        return redirect(route('admin.connect'));
    }

    public function render()
    {
        return view('livewire.admin.waba.disconnect-waba');
    }
}

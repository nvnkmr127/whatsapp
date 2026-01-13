<?php

namespace App\Livewire\Admin\Waba;

use App\Traits\WhatsApp;
use Livewire\Component;
use Str;

class ConnectWaba extends Component
{
    use WhatsApp;

    public $wm_fb_app_id;

    public $wm_fb_app_secret;

    public $wm_business_account_id;

    public $wm_access_token;

    public $is_webhook_connected;

    public $is_whatsmark_connected;

    public $webhook_verify_token;

    protected $messages = [
        'wm_fb_app_id.required'           => 'The Facebook App ID is required.',
        'wm_fb_app_secret.required'       => 'The Facebook App Secret is required.',
        'wm_business_account_id.required' => 'The Whatsapp Business Account ID is required.',
        'wm_access_token.required'        => 'The Whatsapp Access Token is required.',
    ];

    public function mount()
    {
        if (! checkPermission('connect_account.connect')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect()->route('admin.dashboard');
        }
        $whatsapp_settings            = get_settings_by_group('whatsapp');
        $this->wm_fb_app_id           = $whatsapp_settings->wm_fb_app_id;
        $this->wm_fb_app_secret       = $whatsapp_settings->wm_fb_app_secret;
        $this->wm_business_account_id = $whatsapp_settings->wm_business_account_id;
        $this->wm_access_token        = $whatsapp_settings->wm_access_token;
        $this->is_webhook_connected   = $whatsapp_settings->is_webhook_connected;
        $this->is_whatsmark_connected = $whatsapp_settings->is_whatsmark_connected;
        $this->webhook_verify_token   = $whatsapp_settings->webhook_verify_token;

        if (empty($this->webhook_verify_token)) {
            $this->webhook_verify_token = Str::random(16);
            set_setting('whatsapp.webhook_verify_token', $this->webhook_verify_token);
        }

        if ($this->is_webhook_connected == 1 && $this->is_whatsmark_connected == 1) {
            return redirect()->route('admin.waba');
        }
    }

    public function webhookConnect()
    {
        $this->validate([
            'wm_fb_app_id'     => 'required',
            'wm_fb_app_secret' => 'required',
        ]);

        try {
            set_setting('whatsapp.wm_fb_app_id', $this->wm_fb_app_id);
            set_setting('whatsapp.wm_fb_app_secret', $this->wm_fb_app_secret);

            $response = $this->connectWebhook();
            set_setting('whatsapp.is_webhook_connected', $response['status'] ? 1 : 0);

            $this->notify([
                'message' => $response['message'] ?? t('webhook_connect_successfully'),
                'type'    => $response['status'] ? 'success' : 'danger',
            ], true);

            return redirect()->route('admin.connect');
        } catch (\Exception $e) {
            whatsapp_log('Webhook Connection Failed', 'error', [], $e);

            $this->notify(['message' => t('webhook_connect_failed'), 'type' => 'danger'], true);
        }
    }

    public function connectAccount()
    {
        $this->validate([
            'wm_business_account_id' => 'required',
            'wm_access_token'        => 'required',
        ]);

        try {
            set_setting('whatsapp.wm_business_account_id', $this->wm_business_account_id);
            set_setting('whatsapp.wm_access_token', $this->wm_access_token);

            $response = $this->loadTemplatesFromWhatsApp();
            set_setting('whatsapp.is_whatsmark_connected', $response['status']);

            $this->subscribeWebhook();

            $this->notify([
                'message' => $response['status'] ? t('account_connect_successfully') : $response['message'],
                'type'    => $response['status'] ? 'success' : 'danger',
            ], true);

            return $this->redirect(route('admin.connect'));
        } catch (\Exception $e) {
            whatsapp_log('WhatsApp Account Connection Failed', 'error', [], $e);

            $this->notify(['message' => t('account_connect_failed'), 'type' => 'danger'], true);
        }
    }

    public function webhookDisconnect()
    {
        try {
            set_setting('whatsapp.is_webhook_connected', 0);
            $response = $this->disconnectWebhook();
            $this->notify([
                'message' => $response['status'] ? t('webhook_discoonected_successfully') : $response['message'],
                'type'    => $response['status'] ? 'success' : 'danger',
            ]);
        } catch (\Exception $e) {
            whatsapp_log('Webhook Disconnection Failed', 'error', [], $e);
            $this->notify(['message' => t('webhook_disconnect_failed'), 'type' => 'danger']);
        }
    }

    public function render()
    {
        return view('livewire.admin.waba.connect-waba');
    }
}

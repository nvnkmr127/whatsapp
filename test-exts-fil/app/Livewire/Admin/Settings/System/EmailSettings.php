<?php

namespace App\Livewire\Admin\Settings\System;

use App\Mail\TestMail;
use App\Rules\PurifiedInput;
use App\Traits\SendMailTrait;
use Livewire\Component;

class EmailSettings extends Component
{
    use SendMailTrait;

    public ?string $mailer = '';

    public $smtp_port = 0;

    public ?string $smtp_username = '';

    public ?string $smtp_password = '';

    public ?string $smtp_encryption = '';

    public ?string $sender_name = '';

    public ?string $sender_email = '';

    public ?string $sender_mail_path = '';

    public ?string $smtp_host = '';

    public ?string $test_mail;

    public $id;

    protected function rules()
    {
        return [
            'mailer'          => ['nullable', 'string', 'max:255', new PurifiedInput(t('sql_injection_error'))],
            'smtp_port'       => ['nullable', 'numeric'],
            'smtp_username'   => ['nullable', 'string', 'max:255', new PurifiedInput(t('sql_injection_error'))],
            'smtp_password'   => ['nullable', 'string', new PurifiedInput(t('sql_injection_error'))],
            'smtp_encryption' => ['nullable', 'string', 'max:255', new PurifiedInput(t('sql_injection_error'))],
            'sender_name'     => ['nullable', 'string', 'max:255', new PurifiedInput(t('sql_injection_error'))],
            'sender_email'    => 'nullable|email|max:255',
            'smtp_host'       => ['nullable', 'string', 'max:255', new PurifiedInput(t('sql_injection_error'))],
        ];
    }

    public function mount()
    {
        if (! checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->id = $this->getId();
        $this->loadSettings();
    }

    protected function loadSettings()
    {
        $settings = get_settings_by_group('email');

        $this->mailer          = $settings->mailer ?? false;
        $this->smtp_port       = $settings->smtp_port;
        $this->smtp_host       = $settings->smtp_host;
        $this->smtp_username   = $settings->smtp_username;
        $this->smtp_password   = $settings->smtp_password;
        $this->smtp_encryption = $settings->smtp_encryption;
        $this->sender_name     = $settings->sender_name;
        $this->sender_email    = $settings->sender_email;
    }

    public function sendTestEmail($email)
    {
        try {
            if (! isSmtpValid()) {
                return $this->notify(['type' => 'danger', 'message' => t('email_config_is_required')]);
            }

            $result = $this->sendMail($email, new TestMail('smtp-test-mail'));

            if ($result['status']) {
                $this->notify(['type' => 'success', 'message' => t('email_sent_successfully')]);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => t('failed_to_send_test_mail') . $e->getMessage()]);
        }
    }

    public function save()
    {
        if (checkPermission('system_settings.edit')) {
            $this->validate();

            $originalSettings = get_settings_by_group('email');

            $newSettings = [
                'mailer'          => $this->mailer,
                'smtp_port'       => $this->smtp_port,
                'smtp_username'   => $this->smtp_username,
                'smtp_password'   => $this->smtp_password,
                'smtp_encryption' => $this->smtp_encryption,
                'sender_name'     => $this->sender_name,
                'sender_email'    => $this->sender_email,
                'smtp_host'       => $this->smtp_host,
            ];

            // Compare and filter only modified settings
            $modifiedSettings = array_filter($newSettings, function ($value, $key) use ($originalSettings) {
                return $originalSettings->$key !== $value;
            }, ARRAY_FILTER_USE_BOTH);

            // Save only if there are modifications
            if (! empty($modifiedSettings)) {
                set_settings_batch('email', $modifiedSettings);
                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.system.email-settings');
    }
}

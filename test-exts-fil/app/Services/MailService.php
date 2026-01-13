<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class MailService
{
    public function setMailConfig()
    {
        try {
            // Check if required mail settings exist
            if (
                empty(get_setting('email.smtp_host')) || empty(get_setting('email.smtp_username')) || empty(get_setting('email.smtp_password'))
            ) {
                return false;
            }

            config([
                'mail.default'                 => 'smtp',
                'mail.mailers.smtp.driver'     => get_setting('email.mailer', 'smtp'),
                'mail.mailers.smtp.host'       => get_setting('email.smtp_host'),
                'mail.mailers.smtp.port'       => (int) get_setting('email.smtp_port', 587),
                'mail.mailers.smtp.username'   => get_setting('email.smtp_username'),
                'mail.mailers.smtp.password'   => get_setting('email.smtp_password'),
                'mail.mailers.smtp.encryption' => get_setting('email.smtp_encryption', 'tls'),
                'mail.from.address'            => get_setting('email.sender_email', ''),
                'mail.from.name'               => get_setting('email.sender_name', ''),
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendMail($to, $mailable)
    {
        $configResult = $this->setMailConfig();

        if ($configResult === false) {
            return ['type' => 'danger', 'message' => t('email_settings_not_configured')];
        }

        try {
            Mail::mailer('smtp')
                ->to($to)
                ->send($mailable);

            return ['type' => 'success', 'message' => t('email_sent_successfully')];
        } catch (\Exception $e) {
            return ['type' => 'danger', 'message' => t('failed_to_send_email') . ' ' . $e->getMessage()];
        }
    }
}

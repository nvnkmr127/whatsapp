<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;

class TestWhatsAppTemplate extends Command
{
    protected $signature = 'whatsapp:test-template {template} {phone}';

    protected $description = 'Test a WhatsApp template message';

    public function handle(): int
    {
        $template = $this->argument('template');
        $phone    = $this->argument('phone');

        try {
            $whatsapp = new WhatsAppCloudApi([
                'from_phone_number_id' => get_setting('whatsapp.wm_default_phone_number_id'),
                'access_token'         => get_setting('whatsapp.wm_access_token'),
            ]);

            $this->info("Sending template '{$template}' to {$phone}...");

            // Changed language code to en_US
            $result = $whatsapp->sendTemplate(
                $phone,
                $template,
                'en_US',
                null
            );

            $this->info('✓ Message sent successfully!');
            $this->info('Message ID: ' . $result->messageId());

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('× Error: ' . $e->getMessage());

            if (str_contains($e->getMessage(), '{')) {
                $errorData = json_decode($e->getMessage(), true);
                if (isset($errorData['error']['message'])) {
                    $this->error('Error details: ' . $errorData['error']['message']);
                }
            }

            return self::FAILURE;
        }
    }
}

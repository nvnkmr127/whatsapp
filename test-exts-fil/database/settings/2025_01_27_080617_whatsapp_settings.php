<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $settings = [
        'whatsapp.wm_fb_app_id'               => '',
        'whatsapp.wm_fb_app_secret'           => '',
        'whatsapp.wm_business_account_id'     => '',
        'whatsapp.wm_access_token'            => '',
        'whatsapp.wm_default_phone_number'    => '',
        'whatsapp.wm_default_phone_number_id' => '',
        'whatsapp.wm_health_check_time'       => '',
        'whatsapp.wm_health_data'             => '',
        'whatsapp.wm_profile_picture_url'     => '',
        'whatsapp.is_webhook_connected'       => '0',
        'whatsapp.is_whatsmark_connected'     => '0',
    ];

    public function up(): void
    {
        // Add constant settings
        foreach ($this->settings as $key => $value) {
            $this->migrator->add($key, $value);
        }

        // Add environment-dependent and nested settings
        $this->migrator->add('whatsapp.api_version', env('WHATSAPP_API_VERSION', 'v21.0'));
        $this->migrator->add('whatsapp.daily_limit', env('WHATSAPP_DAILY_LIMIT', 1000));
        $this->migrator->add('whatsapp.webhook_verify_token', Str::random(16));

        $this->migrator->add('whatsapp.queue', json_encode([
            'name'        => env('WHATSAPP_QUEUE', 'whatsapp-messages'),
            'connection'  => env('WHATSAPP_QUEUE_CONNECTION', 'redis'),
            'retry_after' => 180,
            'timeout'     => 60,
        ]));

        $this->migrator->add('whatsapp.paths', json_encode([
            'qrcodes' => storage_path('app/public/whatsapp/qrcodes'),
            'media'   => storage_path('app/public/whatsapp/media'),
        ]));

        $this->migrator->add('whatsapp.logging', json_encode([
            'channel'  => env('WHATSAPP_LOG_CHANNEL', 'whatsapp'),
            'detailed' => env('WHATSAPP_DETAILED_LOGGING', true),
        ]));
    }

    public function down(): void
    {
        // Remove constant settings
        foreach (array_keys($this->settings) as $key) {
            $this->migrator->delete($key);
        }

        // Remove dynamic and nested settings
        $this->migrator->delete('whatsapp.api_version');
        $this->migrator->delete('whatsapp.daily_limit');
        $this->migrator->delete('whatsapp.queue');
        $this->migrator->delete('whatsapp.paths');
        $this->migrator->delete('whatsapp.logging');
        $this->migrator->delete('whatsapp.webhook_verify_token');
    }
};

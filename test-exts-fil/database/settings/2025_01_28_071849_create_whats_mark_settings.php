<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $settings = [
        'whats-mark.auto_lead_enabled'              => false,
        'whats-mark.lead_status'                    => null,
        'whats-mark.lead_source'                    => null,
        'whats-mark.lead_assigned_to'               => null,
        'whats-mark.stop_bots_keyword'              => [],
        'whats-mark.restart_bots_after'             => null,
        'whats-mark.enable_webhook_resend'          => false,
        'whats-mark.webhook_resend_method'          => '',
        'whats-mark.whatsapp_data_resend_to'        => '',
        'whats-mark.only_agents_can_chat'           => false,
        'whats-mark.enable_chat_notification_sound' => false,
        'whats-mark.enable_openai_in_chat'          => false,
        'whats-mark.openai_secret_key'              => '',
        'whats-mark.chat_model'                     => '',
        'whats-mark.is_open_ai_key_verify'          => false,
        'whats-mark.enable_auto_clear_chat'         => false,
        'whats-mark.auto_clear_history_time'        => null,
        'whats-mark.wm_version'                     => '',
        'whats-mark.wm_verification_id'             => '',
        'whats-mark.wm_verification_token'          => '',
        'whats-mark.wm_last_verification'           => '',
        'whats-mark.wm_support_until'               => '',
    ];

    public function up(): void
    {
        foreach ($this->settings as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->settings) as $key) {
            if ($this->migrator->exists($key)) {
                $this->migrator->delete($key);
            }
        }
    }
};

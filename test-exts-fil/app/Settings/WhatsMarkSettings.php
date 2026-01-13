<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WhatsMarkSettings extends Settings
{
    // WhatsappAutoLeadSettings
    public ?bool $auto_lead_enabled;

    public $lead_status;

    public $lead_source;

    public $lead_assigned_to;

    // WebHooksSettings
    public ?bool $enable_webhook_resend;

    public ?string $webhook_resend_method;

    public ?string $whatsapp_data_resend_to;

    // StopBotsSettings
    public ?array $stop_bots_keyword;

    public $restart_bots_after;

    // SupportAgentSettings
    public ?bool $only_agents_can_chat;

    // NotificationSoundSettings
    public ?bool $enable_chat_notification_sound;

    // AIIntegrationSettings
    public ?bool $enable_openai_in_chat;

    public ?string $openai_secret_key;

    public ?string $chat_model;

    public ?bool $is_open_ai_key_verify;

    // AutoClearChatSettings
    public ?bool $enable_auto_clear_chat;

    public $auto_clear_history_time;

    public ?string $wm_version;

    public ?string $whatsmark_latest_version;

    public ?string $wm_verification_id;

    public ?string $wm_verification_token;

    public ?string $wm_last_verification;

    public ?string $wm_support_until;

    public ?bool $wm_validate;

    public static function group(): string
    {
        return 'whats-mark';
    }
}

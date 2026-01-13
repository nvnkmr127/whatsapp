<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WebhookSettings extends Settings
{
    public bool $webhook_enabled = false;

    public string $webhook_url = '';

    public array $contacts_actions = [
        'create' => false,
        'read'   => false,
        'update' => false,
        'delete' => false,
    ];

    public array $status_actions = [
        'create' => false,
        'read'   => false,
        'update' => false,
        'delete' => false,
    ];

    public array $source_actions = [
        'create' => false,
        'read'   => false,
        'update' => false,
        'delete' => false,
    ];

    public static function group(): string
    {
        return 'webhook';
    }
}

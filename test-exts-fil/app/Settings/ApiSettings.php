<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ApiSettings extends Settings
{
    public bool $enabled;

    public ?string $token;

    public array $abilities;

    public ?int $rate_limit_max;

    public ?int $rate_limit_decay;

    public ?string $last_used_at;

    public static function group(): string
    {
        return 'api';
    }
}

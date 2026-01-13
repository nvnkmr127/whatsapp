<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?string $site_name;

    public ?string $site_description;

    public ?string $timezone;

    public ?string $date_format;

    public ?string $time_format;

    public ?string $site_logo;

    public ?string $cover_page_image;

    public ?string $favicon;

    public ?string $site_dark_logo;

    public ?string $active_language;

    public static function group(): string
    {
        return 'general';
    }
}

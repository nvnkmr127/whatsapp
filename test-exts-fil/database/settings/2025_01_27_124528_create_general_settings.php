<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $settings = [
        'general.site_name'        => 'Whatsmark',
        'general.site_description' => 'Whatsapp marketing website',
        'general.timezone'         => 'UTC',
        'general.date_format'      => 'd-m-Y',
        'general.time_format'      => '12',
        'general.site_logo'        => '',
        'general.favicon'          => '',
        'general.cover_page_image' => '',
        'general.site_dark_logo'   => '',
        'general.active_language'  => 'en',
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

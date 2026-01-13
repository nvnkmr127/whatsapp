<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    protected array $settings = [
        'pusher.app_id'                    => null,
        'pusher.app_key'                   => null,
        'pusher.app_secret'                => null,
        'pusher.cluster'                   => 'ap2',
        'pusher.real_time_notify'          => false,
        'pusher.desk_notify'               => false,
        'pusher.dismiss_desk_notification' => 5,
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

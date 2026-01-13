<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

if (! function_exists('get_settings_classes')) {
    /**
     * Retrieve and cache the settings classes from the "App\Settings" directory.
     *
     * This function scans the "App\Settings" directory for classes ending with "Settings.php",
     * maps them to their respective kebab-case group names, and caches the result for 1 hour.
     *
     * @return array An associative array where the key is the kebab-case group name
     *               and the value is the fully qualified class name.
     */
    function get_settings_classes(): array
    {
        return Cache::remember('settings.classes', 3600, function () {
            return collect(scandir(app_path('Settings')))
                ->filter(fn ($file) => str_ends_with($file, 'Settings.php'))
                ->mapWithKeys(function ($file) {
                    $className = str_replace('.php', '', $file);
                    $group     = Str::kebab(str_replace('Settings', '', $className));

                    return [$group => "App\\Settings\\{$className}"];
                })
                ->toArray();
        });
    }
}

if (! function_exists('get_settings_by_group')) {
    /**
     * Get all settings for a specific group with in-memory caching.
     */
    function get_settings_by_group(string $group, mixed $default = null): mixed
    {
        // In-memory instance cache for the current request
        static $instances = [];

        try {
            $settingsClasses = get_settings_classes();

            if (! isset($settingsClasses[$group])) {
                return $default;
            }

            // Return the cached instance if it exists
            if (! isset($instances[$group])) {
                $instances[$group] = app($settingsClasses[$group]);
            }

            return $instances[$group];
        } catch (\Throwable $e) {
            report($e);

            return $default;
        }
    }
}

if (! function_exists('get_all_settings')) {
    /**
     * Get all settings from all groups with in-memory caching.
     */
    function get_all_settings(): array
    {
        // In-memory instance cache for the current request
        static $instances = [];

        try {
            return collect(get_settings_classes())
                ->mapWithKeys(function ($class, $group) use (&$instances) {
                    if (! isset($instances[$group])) {
                        $instances[$group] = app($class);
                    }

                    return [$group => $instances[$group]];
                })
                ->toArray();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }
}

if (! function_exists('set_setting')) {
    /**
     * Update a specific setting.
     *
     * @note Use set_settings_batch for better performance when updating multiple settings.
     */
    function set_setting(string $key, mixed $value): bool
    {
        try {
            [$group, $setting] = explode('.', $key);
            $settingsClasses   = get_settings_classes();

            if (! isset($settingsClasses[$group])) {
                return false;
            }

            $settings = app($settingsClasses[$group]);

            // Only update if the setting exists
            if (! property_exists($settings, $setting)) {
                return false;
            }

            $settings->$setting = $value;
            $settings->save();

            // Clear specific setting cache
            Cache::forget("settings.{$group}.{$setting}");

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}

if (! function_exists('set_settings_batch')) {
    /**
     * Update multiple settings for a specific group.
     */
    function set_settings_batch(string $group, array $settings): bool
    {
        try {
            $settingsClasses = get_settings_classes();

            if (! isset($settingsClasses[$group])) {
                return false;
            }

            $settingsObject   = app($settingsClasses[$group]);
            $settingsToUpdate = [];

            foreach ($settings as $key => $value) {
                if (property_exists($settingsObject, $key)) {
                    $settingsObject->$key   = $value;
                    $settingsToUpdate[$key] = $value;
                }
            }

            if (! empty($settingsToUpdate)) {
                $settingsObject->save();

                foreach ($settingsToUpdate as $key => $value) {
                    Cache::forget("settings.{$group}.{$key}");
                }
            }

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}

if (! function_exists('get_setting')) {
    /**
     * Retrieve a specific setting value by key with caching.
     *
     * This function fetches a setting by its "group.setting" key format.
     * It uses in-memory caching for quick access and caches the value for 30 minutes.
     *
     * @param  string $key     The setting key in "group.setting" format.
     * @param  mixed  $default The default value to return if the setting is not found.
     * @return mixed  The setting value or the default value if the setting is not found.
     */
    function get_setting(string $key, mixed $default = null): mixed
    {
        try {
            [$group, $setting] = explode('.', $key);

            $settings = get_settings_by_group($group);

            // Cache individual setting values for 30 minutes to reduce DB calls if settings don't change often
            return Cache::remember("settings.{$group}.{$setting}", now()->addMinutes(30), function () use ($settings, $setting, $default) {
                return $settings->$setting ?? $default;
            });
        } catch (\Throwable $e) {
            report($e);

            return $default;
        }
    }
}

if (! function_exists('settings')) {
    /**
     * Main settings function that handles all operations.
     * - If no key is provided, returns all settings.
     * - If a key and value are provided, sets the setting.
     * - Otherwise, gets the setting.
     */
    function settings(?string $key = null, mixed $value = null, mixed $default = null): mixed
    {
        return is_null($key)
            ? get_all_settings()
            : (! is_null($value)
                ? set_setting($key, $value)
                : get_setting($key, $default));
    }
}

if (! function_exists('test_settings')) {
    /**
     * Test the retrieval of general settings and cache status.
     *
     * This function attempts to retrieve the GeneralSettings instance,
     * outputs specific settings, and verifies the cache and helper function.
     *
     * @return bool Returns false if an error occurs.
     */
    function test_settings()
    {
        try {
            $settings = app(\App\Settings\GeneralSettings::class);
            ([
                'settings'  => $settings,
                'site_name' => $settings->site_name,
                'cache'     => Cache::get('settings.system.site_name'),
                'helper'    => settings('system.site_name'),
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('get_settings_groups')) {
    /**
     * Get all available settings groups.
     */
    function get_settings_groups(): array
    {
        return array_keys(settings()->toArray());
    }
}

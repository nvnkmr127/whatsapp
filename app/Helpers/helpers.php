<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (! function_exists('is_setting_sensitive')) {
    function is_setting_sensitive($key)
    {
        $sensitiveKeywords = ['api_key', 'secret', 'password', 'token', 'private'];
        foreach ($sensitiveKeywords as $keyword) {
            if (str_contains(strtolower($key), $keyword)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('get_setting')) {
    function get_setting($key, $default = null)
    {
        return Cache::rememberForever('setting_'.$key, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            if (! $setting) {
                return $default;
            }

            $value = $setting->value;
            if (is_setting_sensitive($key) && $value) {
                try {
                    return decrypt($value);
                } catch (\Exception $e) {
                    // Fallback to plain value if decryption fails (for existing values)
                    return $value;
                }
            }

            return $value;
        });
    }
}

if (! function_exists('set_setting')) {
    function set_setting($key, $value, $group = null)
    {
        $storedValue = $value;
        if (is_setting_sensitive($key) && $value) {
            $storedValue = encrypt($value);
        }

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'group' => $group]
        );
        Cache::forget('setting_'.$key);
    }
}

if (! function_exists('audit')) {
    function audit(string $event, $userId = null, $identifier = null, ?string $provider = null, array $metadata = [])
    {
        return \App\Services\AuditService::log($event, $userId, $identifier, $provider, $metadata);
    }
}

if (! function_exists('money')) {
    function money($amount, $currency = 'USD')
    {
        return Illuminate\Support\Number::currency((float) $amount, $currency);
    }
}

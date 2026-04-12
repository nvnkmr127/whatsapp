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

if (! function_exists('t')) {
    function t($key)
    {
        return __($key);
    }
}

if (! function_exists('checkPermission')) {
    function checkPermission($permission)
    {
        $permissions = is_array($permission) ? $permission : [$permission];
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        foreach ($permissions as $perm) {
            if ($user->can($perm)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('whatsapp_log')) {
    function whatsapp_log($message, $level = 'info', $context = [], $exception = null)
    {
        if ($exception) {
            $context['exception'] = $exception->getMessage();
            $context['trace'] = $exception->getTraceAsString();
        }
        \Illuminate\Support\Facades\Log::channel('daily')->log($level, 'WA API: '.$message, $context);
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
        return '$'.number_format((float) $amount, 2);
    }
}

if (! function_exists('flash_message')) {
    /**
     * Flash a message to the session for toast notifications.
     * types: success, error, warning, info
     */
    function flash_message($message, $type = 'success')
    {
        session()->flash($type, $message);
    }
}

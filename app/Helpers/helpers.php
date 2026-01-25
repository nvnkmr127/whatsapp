<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('get_setting')) {
    function get_setting($key, $default = null)
    {
        return Cache::rememberForever('setting_' . $key, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }
}

if (!function_exists('set_setting')) {
    function set_setting($key, $value, $group = null)
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
        Cache::forget('setting_' . $key);
    }
}

if (!function_exists('t')) {
    function t($key)
    {
        return __($key);
    }
}

if (!function_exists('checkPermission')) {
    function checkPermission($permission)
    {
        $permissions = is_array($permission) ? $permission : [$permission];
        $user = auth()->user();

        if (!$user) {
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

if (!function_exists('whatsapp_log')) {
    function whatsapp_log($message, $level = 'info', $context = [], $exception = null)
    {
        if ($exception) {
            $context['exception'] = $exception->getMessage();
            $context['trace'] = $exception->getTraceAsString();
        }
        \Illuminate\Support\Facades\Log::channel('daily')->log($level, "WA API: " . $message, $context);
    }
}
if (!function_exists('audit')) {
    function audit(string $event, $userId = null, $identifier = null, ?string $provider = null, array $metadata = [])
    {
        return \App\Services\AuditService::log($event, $userId, $identifier, $provider, $metadata);
    }
}

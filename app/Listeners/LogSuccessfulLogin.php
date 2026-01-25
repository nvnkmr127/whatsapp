<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;

class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->user;
        $ip = request()->ip();
        $userAgent = request()->userAgent();

        // 1. Audit Log (Core)
        audit('auth.login', "User '{$user->name}' logged in.", $user);

        // 2. Proactive Security Check
        $security = $user->security_metadata ?? ['known_ips' => [], 'known_devices' => []];
        $isNewIp = !in_array($ip, $security['known_ips'] ?? []);
        $isNewDevice = !in_array($userAgent, $security['known_devices'] ?? []);

        if ($isNewIp || $isNewDevice) {
            // Only notify if there's history (don't alert on first ever login)
            if (!empty($security['known_ips'])) {
                $user->notify(new \App\Notifications\LoginSecurityNotification($ip, $userAgent, now()->toDayDateTimeString()));
            }

            // Update Known list
            $security['known_ips'][] = $ip;
            $security['known_devices'][] = $userAgent;

            // Keep history lean (last 10)
            $security['known_ips'] = array_slice(array_unique($security['known_ips']), -10);
            $security['known_devices'] = array_slice(array_unique($security['known_devices']), -10);

            $user->security_metadata = $security;
            $user->save();
        }
    }
}

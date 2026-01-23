<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if maintenance mode is enabled via settings
        // We use the helper get_setting() providing a default of false
        $isMaintenanceMode = filter_var(get_setting('maintenance_mode', false), FILTER_VALIDATE_BOOLEAN);

        if (!$isMaintenanceMode) {
            return $next($request);
        }

        // Allow access to essential auth routes so admins can still log in
        if (
            in_array($request->route()?->getName(), [
                'login',
                'logout',
                'password.request',
                'password.email',
                'password.reset',
                'two-factor.login',
                'two-factor.challenge'
            ])
        ) {
            return $next($request);
        }

        // Allow access if user is logged in AND has permission to manage settings
        if ($request->user() && $request->user()->can('manage-settings')) {
            // Optional: Share a variable with view to show a banner (handled by GlobalSettingsComposer usually)
            return $next($request);
        }

        // Otherwise, return 503 Service Unavailable
        return abort(503, 'System is currently in maintenance mode.');
    }
}

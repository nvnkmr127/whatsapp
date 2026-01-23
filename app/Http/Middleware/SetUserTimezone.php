<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            $timezone = Auth::user()->currentTeam->timezone;

            if ($timezone) {
                // Set Laravel's config timezone
                Config::set('app.timezone', $timezone);

                // Set PHP's default timezone
                date_default_timezone_set($timezone);
            }
        }

        return $next($request);
    }
}

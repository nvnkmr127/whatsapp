<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantSubscription
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $team = $request->user()?->currentTeam;

        if (!$team) {
            return $next($request);
        }

        // List of statuses that are allowed access
        $allowedStatuses = ['active', 'trial'];

        // If you are a Super Admin, you bypass this check
        if ($request->user()->is_super_admin) {
            return $next($request);
        }

        if (!in_array($team->subscription_status, $allowedStatuses)) {
            abort(403, 'Your subscription is inactive. Please contact billing.');
        }

        return $next($request);
    }
}

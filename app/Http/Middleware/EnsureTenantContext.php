<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Check for API Tenant Context Header
        if ($request->hasHeader('X-Tenant-ID')) {
            $requestedTenantId = $request->header('X-Tenant-ID');

            // Verify the user actually belongs to this team
            $tenant = $user->allTeams()->firstWhere('id', $requestedTenantId);

            if (! $tenant) {
                abort(403, 'You do not have access to the requested tenant.');
            }

            // Temporarily set the current team ID without saving to DB
            $user->current_team_id = $tenant->id;
            // Swapping the relation in memory for this request lifecycle
            $user->setRelation('currentTeam', $tenant);
        }

        if (! $user->currentTeam) {
            if ($request->wantsJson()) {
                abort(403, 'No tenant (team) selected for the current user.');
            }
            abort(403, 'No active workspace selected. Please select or create a team.');
        }

        return $next($request);
    }
}

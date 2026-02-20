<?php

namespace App\Http\Middleware;

use App\Services\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckTenantSubscription
 * ════════════════════════
 * API / tenant-layer subscription gate. Delegates ALL status decisions to
 * EntitlementService — never inspects Team columns directly.
 */
class CheckTenantSubscription
{
    public function __construct(private readonly EntitlementService $entitlements)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super Admins bypass all subscription checks
        if ($user?->is_super_admin) {
            return $next($request);
        }

        $team = $user?->currentTeam;

        if (!$team) {
            return $next($request);
        }

        $e = $this->entitlements->for($team);

        if (!$e->active()) {
            abort(403, "Your subscription is inactive ({$e->statusLabel()}). Please contact billing.");
        }

        return $next($request);
    }
}

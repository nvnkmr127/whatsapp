<?php

namespace App\Http\Middleware;

use App\Services\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckSubscription
 * ═════════════════
 * Web-layer subscription guard. Delegates ALL status decisions to
 * EntitlementService — never inspects Team columns directly.
 */
class CheckSubscription
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super Admins bypass all subscription checks
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        $team = $user?->currentTeam;

        if ($team) {
            $e = $this->entitlements->for($team);

            if ($e->expired()) {
                $label = $e->statusLabel();

                if ($e->onTrial() === false && $e->inGrace() === false) {
                    // Trial actually expired
                    session()->flash('flash.banner', 'Your trial period has expired. Please upgrade to continue.');
                } else {
                    session()->flash('flash.banner', "Your subscription has ended ({$label}). Please renew to restore access.");
                }

                session()->flash('flash.bannerStyle', 'danger');

                return redirect()->route('teams.show', $team->id);
            }
        }

        return $next($request);
    }
}

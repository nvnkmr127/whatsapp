<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Plan;

class CheckPlanFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $feature  The feature to check (e.g., 'campaigns', 'automations')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $team = $user->currentTeam;

        if (!$team) {
            abort(403, 'No active team found.');
        }

        // Super admins bypass all restrictions
        if ($user->is_super_admin) {
            return $next($request);
        }

        // Check if team has the required feature (Service-level logic handles plans + addons)
        if (!$team->hasFeature($feature)) {
            // Return a nice upgrade prompt instead of just 403
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Feature not available',
                    'message' => "The '{$feature}' feature is not available on your current plan or add-ons.",
                    'upgrade_required' => true,
                ], 403);
            }

            return redirect()
                ->route('analytics') // Redirect to billing/analytics page
                ->with('error', "The '{$feature}' feature is not available on your current plan. Please upgrade to access this feature.");
        }

        return $next($request);
    }
}

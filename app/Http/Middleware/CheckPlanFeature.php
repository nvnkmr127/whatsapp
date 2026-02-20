<?php

namespace App\Http\Middleware;

use App\Services\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckPlanFeature
 * ════════════════
 * Route-level feature gate. Delegates ENTIRELY to EntitlementService.
 * No trial / subscription logic lives here.
 *
 * Usage in routes:
 *   ->middleware('plan_feature:ai')
 *   ->middleware('plan_feature:automations')
 *   ->middleware('plan_feature:analytics')
 *   ->middleware('plan_feature:commerce')
 *   ->middleware('plan_feature:api_access')
 *   ->middleware('plan_feature:calling')
 */
class CheckPlanFeature
{
    public function __construct(private readonly EntitlementService $entitlements)
    {
    }

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super Admins bypass all feature gates
        if ($user->is_super_admin) {
            return $next($request);
        }

        $team = $user->currentTeam;

        if (!$team) {
            abort(403, 'No active team found.');
        }

        $e = $this->entitlements->for($team);

        if (!$e->hasFeature($feature)) {
            $reason = $e->denialReason($feature)
                ?? "The '{$feature}' feature is not included in your current plan.";

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Feature not available',
                    'feature' => $feature,
                    'message' => $reason,
                    'status_label' => $e->statusLabel(),
                    'upgrade_required' => !$e->expired(), // expired = different CTA
                    'entitlement' => [
                        'active' => $e->active(),
                        'on_trial' => $e->onTrial(),
                        'offer_eligible' => $e->offerEligible(),
                        'trial_days_left' => $e->trialDaysRemaining(),
                    ],
                ], 403);
            }

            return redirect()
                ->route('billing')
                ->with('error', $reason);
        }

        return $next($request);
    }
}

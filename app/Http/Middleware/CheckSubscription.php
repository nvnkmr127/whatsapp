<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super Admins bypass subscription checks
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        $team = $user ? $user->currentTeam : null;

        if ($team) {
            // 1. Check if trial has expired
            if ($team->subscription_status === 'trial' && $team->trial_ends_at && $team->trial_ends_at->isPast()) {
                session()->flash('flash.banner', 'Your trial period has expired. Please upgrade to continue.');
                session()->flash('flash.bannerStyle', 'danger');

                return redirect()->route('teams.show', $team->id);
            }

            // 2. Check for canceled subscriptions past their end date
            if ($team->subscription_status === 'canceled' && $team->subscription_ends_at && $team->subscription_ends_at->isPast()) {
                session()->flash('flash.banner', 'Your subscription has ended. Please renew to restore access.');
                session()->flash('flash.bannerStyle', 'danger');

                return redirect()->route('teams.show', $team->id);
            }
        }

        return $next($request);
    }
}

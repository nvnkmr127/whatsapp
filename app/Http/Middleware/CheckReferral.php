<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckReferral
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Capture Referral Code
        if ($request->has('ref')) {
            $code = $request->query('ref');
            if (\App\Models\Affiliate::where('code', $code)->exists()) {
                $response->withCookie(cookie('referral_code', $code, 43200)); // 30 days
            }
        }

        // 2. Capture UTM Parameters
        $utms = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        foreach ($utms as $utm) {
            if ($request->has($utm)) {
                $response->withCookie(cookie($utm, $request->query($utm), 43200)); // 30 days
            }
        }

        return $response;
    }
}

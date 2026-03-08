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
        if ($request->has('ref')) {
            $code = $request->query('ref');
            
            // Check if affiliate exists
            if (\App\Models\Affiliate::where('code', $code)->exists()) {
                // Store in cookie for 30 days
                return $next($request)->withCookie(cookie('referral_code', $code, 43200));
            }
        }

        return $next($request);
    }
}

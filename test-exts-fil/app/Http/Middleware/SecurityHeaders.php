<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return $response->withHeaders([
            'X-XSS-Protection'        => '1; mode=block',
            'X-Frame-Options'         => 'SAMEORIGIN',
            'X-Content-Type-Options'  => 'nosniff',
            'Content-Security-Policy' => "default-src 'self'; font-src 'self' fonts.googleapis.com fonts.gstatic.com data:; img-src 'self' data: *; script-src 'self' 'unsafe-inline'; style-src 'self' fonts.googleapis.com 'unsafe-inline';",
        ]);
    }
}

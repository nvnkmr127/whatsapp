<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TraceContext;

class AssignCorrelationId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if correlation ID exists in headers (from load balancer or client)
        $traceId = $request->header('X-Correlation-ID')
            ?? $request->header('X-Request-ID')
            ?? (string) Str::uuid();

        // 2. Set Trace Context for this request
        TraceContext::set($traceId);

        // 3. Add to Request object for easy access in controllers
        $request->merge(['trace_id' => $traceId]);

        // 4. Process Request
        $response = $next($request);

        // 5. Return ID in response headers for debugging
        $response->headers->set('X-Correlation-ID', $traceId);

        return $response;
    }
}

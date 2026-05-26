<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogMobileRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Capture correlation/trace ID if set by AssignCorrelationId middleware
        $traceId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        // Prepare request details for logging
        $method = $request->method();
        $url = $request->fullUrl();
        $ip = $request->ip();

        // Sanitize headers: exclude Authorization header value for security
        $headers = $request->headers->all();
        if (isset($headers['authorization'])) {
            $headers['authorization'] = ['Bearer [REDACTED]'];
        }

        // Sanitize sensitive request parameters
        $input = $request->all();
        $sensitiveKeys = ['password', 'password_confirmation', 'otp', 'code', 'token', 'access_token'];
        foreach ($sensitiveKeys as $key) {
            if (array_key_exists($key, $input)) {
                $input[$key] = '[REDACTED]';
            }
        }

        Log::channel('mobile')->info("[REQUEST] {$method} {$url} | IP: {$ip}", [
            'trace_id' => $traceId,
            'headers' => $headers,
            'body' => $input,
        ]);

        $startTime = microtime(true);

        try {
            $response = $next($request);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $status = $response->getStatusCode();

            // Prepare response content
            $content = $response->getContent();
            $decoded = null;
            if ($response->headers->get('Content-Type') === 'application/json' || str_contains($response->headers->get('Content-Type') ?? '', 'json')) {
                $decoded = json_decode($content, true);
            } else {
                $decoded = substr($content, 0, 1000) . (strlen($content) > 1000 ? '... [TRUNCATED]' : '');
            }

            Log::channel('mobile')->info("[RESPONSE] {$method} {$url} | Status: {$status} | Duration: {$duration}ms", [
                'trace_id' => $traceId,
                'status' => $status,
                'body' => $decoded,
            ]);

            return $response;
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::channel('mobile')->error("[EXCEPTION] {$method} {$url} | Duration: {$duration}ms", [
                'trace_id' => $traceId,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(10)->map(fn($t) => [
                    'file' => $t['file'] ?? null,
                    'line' => $t['line'] ?? null,
                    'function' => $t['function'] ?? null,
                    'class' => $t['class'] ?? null,
                ])->toArray(),
            ]);

            throw $e;
        }
    }
}

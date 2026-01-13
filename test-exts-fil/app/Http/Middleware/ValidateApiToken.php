<?php

namespace App\Http\Middleware;

use App\Settings\ApiSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiToken
{
    protected ApiSettings $settings;

    public function __construct(ApiSettings $settings)
    {
        $this->settings = $settings;
    }

    public function handle(Request $request, Closure $next, ?string $ability = null)
    {
        if (! $this->settings->enabled) {
            return response()->json([
                'status'  => 'error',
                'message' => 'API access is disabled',
            ], 403);
        }

        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'status'  => 'error',
                'message' => 'API token is required',
            ], 401);
        }

        // Validate token
        if ($token !== $this->settings->token) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid API token',
            ], 401);
        }

        // Check specific ability if provided
        if ($ability && ! in_array($ability, $this->settings->abilities)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token does not have the required ability: ' . $ability,
            ], 403);
        }

        // Rate limiting
        $key          = 'api_token_' . $token;
        $maxAttempts  = $this->settings->rate_limit_max   ?? 60;
        $decayMinutes = $this->settings->rate_limit_decay ?? 1;

        // Check if the user has exceeded the rate limit
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message'     => t('too_many_requests'),
                'retry_after' => $retryAfter,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Increment attempts
        RateLimiter::hit($key, $decayMinutes * 60);

        // Update last used timestamp
        $this->settings->last_used_at = now();
        $this->settings->save();

        return $next($request);
    }
}

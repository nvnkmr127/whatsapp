<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * BlockTrialFieldsViaApi
 * ══════════════════════
 * Prevents external API callers from directly writing sensitive billing /
 * offer fields onto team or user records through any API endpoint.
 *
 * Protected fields — cannot arrive in any API request body:
 *
 *   subscription_status    – determines billing tier
 *   subscription_plan      – linked to feature gates
 *   trial_ends_at          – trial expiry date
 *   subscription_ends_at   – paid plan expiry
 *   offer_claimed_at       – first offer claim timestamp
 *   offer_excluded         – Super Admin exclusion flag
 *   offer_converted_churned – converted-then-churned flag
 *   has_claimed_offer      – user-level permanent claim flag
 *   is_super_admin         – privilege escalation guard
 *
 * If a protected field is detected the middleware:
 *   1. Strips the field from the request input silently.
 *   2. Logs a tamper attempt to the audit log with full context.
 *   3. Returns a 422 response — NOT silently — so callers know the
 *      field is forbidden rather than thinking it was accepted.
 *
 * Applied to: all routes in the `auth:sanctum + tenant` API group.
 */
class BlockTrialFieldsViaApi
{
    // Fields that can ONLY be written by the system, never by API callers
    private const PROTECTED_FIELDS = [
        'subscription_status',
        'subscription_plan',
        'trial_ends_at',
        'subscription_ends_at',
        'subscription_grace_ends_at',
        'offer_claimed_at',
        'offer_excluded',
        'offer_converted_churned',
        'offer_converted_churned',
        'has_claimed_offer',
        'is_super_admin',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        Log::info('[DEBUG] [API] BlockTrialFieldsViaApi check', [
            'method' => $request->method(),
            'path' => $request->path(),
            'all_keys' => array_keys($request->all()),
            'user_id' => $request->user()?->id,
            'team_id' => $request->user()?->current_team_id,
        ]);

        $attempted = array_intersect(
            array_keys($request->all()),
            self::PROTECTED_FIELDS
        );

        if (! empty($attempted)) {
            $this->logTamperAttempt($request, $attempted);

            return response()->json([
                'message' => 'The following fields cannot be set via the API: '.
                    implode(', ', $attempted),
                'fields' => $attempted,
            ], 422);
        }

        return $next($request);
    }

    private function logTamperAttempt(Request $request, array $fields): void
    {
        $user = $request->user();

        Log::warning('BlockTrialFieldsViaApi: protected field write attempted via API', [
            'user_id' => $user?->id,
            'team_id' => $user?->current_team_id,
            'ip' => $request->ip(),
            'fields' => $fields,
            'endpoint' => $request->method().' '.$request->path(),
            'user_agent' => $request->userAgent(),
        ]);

        AuditService::log(
            event: 'Offer.API.TamperAttempt',
            userId: $user,
            identifier: implode(', ', $fields),
            provider: 'api',
            metadata: [
                'endpoint' => $request->method().' '.$request->path(),
                'fields' => $fields,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );
    }
}

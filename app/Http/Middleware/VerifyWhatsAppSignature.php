<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsAppSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip verification for verify token requests (GET)
        if ($request->isMethod('get')) {
            return $next($request);
        }

        $signature = $request->header('X-Hub-Signature-256');

        try {
            \Illuminate\Support\Facades\Log::channel('whatsapp')->debug('WhatsApp webhook signature received: '.($signature ? 'present' : 'MISSING'));
        } catch (\Exception $e) {
            // Silently fail
        }

        if (! $signature) {
            Log::warning('WhatsApp Webhook: Missing Signature');

            // For strict security, we should reject.
            // But verify if local dev needs bypass (optional)
            return response('Missing Signature', 403);
        }

        $appSecret = config('whatsapp.app_secret'); // Centralized config source

        if (! $appSecret) {
            if (app()->environment('production')) {
                Log::critical('WhatsApp Webhook: APP_SECRET not configured in PRODUCTION! Rejecting request.');

                return response('Server misconfiguration - signature verification required', 500);
            }

            Log::warning('WhatsApp Webhook: APP_SECRET not configured. Skipping signature verification (DEV MODE ONLY).');

            return $next($request);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('WhatsApp Webhook: Invalid Signature', [
                'app_env' => config('app.env'),
            ]);

            return response('Invalid Signature', 403);
        }

        return $next($request);
    }
}

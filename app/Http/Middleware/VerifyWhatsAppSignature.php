<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

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

        if (!$signature) {
            Log::warning('WhatsApp Webhook: Missing Signature');
            // For strict security, we should reject.
            // But verify if local dev needs bypass (optional)
            return response('Missing Signature', 403);
        }

        $appSecret = config('services.whatsapp.client_secret'); // Make sure this is set in .env

        if (!$appSecret) {
            Log::warning('WhatsApp Webhook: APP_SECRET not configured. Skipping signature verification (INSECURE for Production).');
            return $next($request);
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        if (!hash_equals($expected, $signature)) {
            Log::warning('WhatsApp Webhook: Invalid Signature', [
                'expected' => $expected,
                'received' => $signature
            ]);
            return response('Invalid Signature', 403);
        }

        return $next($request);
    }
}

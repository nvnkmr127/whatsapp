<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookAuthService
{
    /**
     * Verify webhook authentication based on method
     */
    public function verify(Request $request, string $authMethod, $authConfig): bool
    {
        $config = is_array($authConfig) ? $authConfig : (is_string($authConfig) ? json_decode($authConfig, true) : []);
        $config = $config ?? [];

        return match ($authMethod) {
            'hmac' => $this->verifyHmac($request, $config),
            'api_key' => $this->verifyApiKey($request, $config),
            'basic' => $this->verifyBasicAuth($request, $config),
            'none' => true,
            default => false,
        };
    }

    /**
     * Verify HMAC signature
     */
    protected function verifyHmac(Request $request, array $config): bool
    {
        $header = $config['header'] ?? 'X-Webhook-Signature';
        $secret = $config['secret'] ?? '';
        $algorithm = $config['algorithm'] ?? 'sha256';

        $signature = $request->header($header);

        if (!$signature) {
            Log::warning('HMAC verification failed: No signature header found', [
                'expected_header' => $header,
            ]);
            return false;
        }

        $payload = $request->getContent();
        $computedSignature = hash_hmac($algorithm, $payload, $secret);

        // Handle different signature formats
        if (str_contains($header, 'Shopify')) {
            // Shopify sends base64 encoded signature
            $computedSignature = base64_encode(hash_hmac($algorithm, $payload, $secret, true));
        } elseif (str_contains($header, 'Stripe')) {
            // Stripe signature format: t=timestamp,v1=signature
            return $this->verifyStripeSignature($signature, $payload, $secret);
        }

        $isValid = hash_equals($computedSignature, $signature);

        if (!$isValid) {
            Log::warning('HMAC verification failed: Signature mismatch', [
                'header' => $header,
                'expected' => substr($computedSignature, 0, 10) . '...',
                'received' => substr($signature, 0, 10) . '...',
            ]);
        }

        return $isValid;
    }

    /**
     * Verify Stripe signature (special format)
     */
    protected function verifyStripeSignature(string $signature, string $payload, string $secret): bool
    {
        $elements = explode(',', $signature);
        $timestamp = null;
        $signatures = [];

        foreach ($elements as $element) {
            [$key, $value] = explode('=', $element, 2);
            if ($key === 't') {
                $timestamp = $value;
            } elseif (str_starts_with($key, 'v')) {
                $signatures[] = $value;
            }
        }

        if (!$timestamp || empty($signatures)) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify API key
     */
    protected function verifyApiKey(Request $request, array $config): bool
    {
        $header = $config['header'] ?? 'X-API-Key';
        $expectedKey = $config['key'] ?? '';

        $providedKey = $request->header($header);

        if (!$providedKey) {
            Log::warning('API Key verification failed: No key header found', [
                'expected_header' => $header,
                'available_headers' => array_keys($request->headers->all()),
            ]);
            return false;
        }

        $isValid = hash_equals($expectedKey, $providedKey);

        if (!$isValid) {
            Log::warning('API Key verification failed: Key mismatch', [
                'header' => $header,
                'received_key_preview' => substr($providedKey, 0, 4) . '...',
            ]);
        }

        return $isValid;
    }

    /**
     * Verify Basic Authentication
     */
    protected function verifyBasicAuth(Request $request, array $config): bool
    {
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            Log::warning('Basic Auth verification failed: No valid Authorization header found', [
                'has_header' => (bool) $authHeader,
            ]);
            return false;
        }

        $credentials = base64_decode(substr($authHeader, 6));
        $parts = explode(':', $credentials, 2);

        if (count($parts) < 2) {
            Log::warning('Basic Auth verification failed: Malformed credentials');
            return false;
        }

        [$providedUsername, $providedPassword] = $parts;

        $isValid = hash_equals($username, $providedUsername) && hash_equals($password, $providedPassword);

        if (!$isValid) {
            Log::warning('Basic Auth verification failed: Credentials mismatch', [
                'username_match' => hash_equals($username, $providedUsername),
            ]);
        }

        return $isValid;
    }

    /**
     * Platform-specific verification helpers
     */
    public function verifyShopify(Request $request, string $secret): bool
    {
        return $this->verifyHmac($request, [
            'header' => 'X-Shopify-Hmac-SHA256',
            'secret' => $secret,
            'algorithm' => 'sha256',
        ]);
    }

    public function verifyStripe(Request $request, string $secret): bool
    {
        return $this->verifyHmac($request, [
            'header' => 'Stripe-Signature',
            'secret' => $secret,
            'algorithm' => 'sha256',
        ]);
    }

    public function verifyWooCommerce(Request $request, string $secret): bool
    {
        return $this->verifyHmac($request, [
            'header' => 'X-WC-Webhook-Signature',
            'secret' => $secret,
            'algorithm' => 'sha256',
        ]);
    }
}

<?php

namespace App\Core\WhatsApp;

use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ManagementClient
{
    protected WhatsAppClient $client;

    protected CredentialResolver $resolver;
    protected ?Team $team = null;
    protected bool $skipAppSecretProof = false;

    public function __construct(WhatsAppClient $client, CredentialResolver $resolver)
    {
        $this->client = $client;
        $this->resolver = $resolver;
    }

    /**
     * Set the team context.
     */
    public function forTeam(Team $team): self
    {
        $this->team = $team;
        $this->client->forTeam($team);

        return $this;
    }

    /**
     * Disable appsecret_proof for this client.
     */
    public function skipAppSecretProof(bool $skip = true): self
    {
        $this->skipAppSecretProof = $skip;

        return $this;
    }

    /**
     * Exchange short-lived token for long-lived.
     */
    public function exchangeToken(string $shortLivedToken): array
    {
        $creds = $this->team ? $this->resolver->resolve($this->team) : null;
        $appId = $creds['app_id'] ?? config('whatsapp.app_id');
        $appSecret = $creds['app_secret'] ?? config('whatsapp.app_secret');

        if (! $appId || ! $appSecret) {
            return ['success' => false, 'error' => 'App ID or Secret missing'];
        }

        $url = 'https://graph.facebook.com/oauth/access_token';
        $response = Http::get($url, [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if ($response->failed()) {
            return ['status' => false, 'error' => $response->json('error.message') ?? 'Exchange failed'];
        }

        return array_merge(['status' => true], $response->json());
    }

    /**
     * Subscribe to webhooks.
     *
     * Retry ladder for embedded signup (USER tokens) which frequently hit permission errors:
     *   1. User token + appsecret_proof  (standard)
     *   2. User token without proof      (if proof is wrong/mismatched)
     *   3. App Access Token              (app_id|app_secret) — Meta's recommended fallback for #200
     *
     * The App Access Token is a server-to-server credential that has whatsapp_business_management
     * by default and does NOT require appsecret_proof.
     */
    public function subscribeToWebhooks(string $wabaId, string $token): array
    {
        $creds     = $this->team ? $this->resolver->resolve($this->team) : null;
        $appId     = $creds['app_id']     ?? config('whatsapp.app_id');
        $appSecret = $creds['app_secret'] ?? config('whatsapp.app_secret');

        $version = config('whatsapp.api_version', 'v21.0');
        $url     = "https://graph.facebook.com/{$version}/{$wabaId}/subscribed_apps";

        // --- Attempt 1: user token + appsecret_proof ---
        $result = $this->doSubscribePost($url, $token, $appId, $appSecret, $wabaId, withProof: !$this->skipAppSecretProof);

        if ($result['status'] ?? false) {
            return $result;
        }

        $code = $result['meta']['code'] ?? 0;
        $msg  = $result['message'] ?? '';

        // --- Attempt 2: user token WITHOUT proof (proof mismatch) ---
        $isProofMismatch = ($code === 100 && str_contains($msg, 'appsecret_proof'))
                        || str_contains($msg, 'Invalid appsecret_proof');

        if ($isProofMismatch && !$this->skipAppSecretProof) {
            Log::info('WhatsApp ManagementClient: appsecret_proof mismatch — retrying without proof', [
                'trace_id' => \App\Services\TraceContext::getTraceId(),
                'team_id'  => $this->team?->id,
                'waba_id'  => $wabaId,
            ]);
            $result = $this->doSubscribePost($url, $token, $appId, $appSecret, $wabaId, withProof: false);

            if ($result['status'] ?? false) {
                return $result;
            }

            $code = $result['meta']['code'] ?? 0;
            $msg  = $result['message'] ?? '';
        }

        // --- Attempt 3: App Access Token (app_id|app_secret) ---
        // Meta #200 = token lacks whatsapp_business_management. The App Access Token is the
        // documented fix — it's a first-party credential with full management permissions.
        $isPermissionError = $code === 200
                          || str_contains($msg, '#200')
                          || str_contains($msg, 'Permissions error')
                          || str_contains($msg, 'permission');

        if ($isPermissionError && $appId && $appSecret) {
            $appToken = "{$appId}|{$appSecret}";

            Log::info('WhatsApp ManagementClient: #200 Permission error — retrying with App Access Token', [
                'trace_id' => \App\Services\TraceContext::getTraceId(),
                'team_id'  => $this->team?->id,
                'waba_id'  => $wabaId,
                'hint'     => 'USER token lacks whatsapp_business_management scope.',
            ]);

            // App tokens don't need appsecret_proof
            $result = $this->doSubscribePost($url, $appToken, $appId, $appSecret, $wabaId, withProof: false);
        }

        return $result;
    }

    /**
     * Internal: perform the actual POST to subscribed_apps.
     */
    private function doSubscribePost(string $url, string $token, ?string $appId, ?string $appSecret, string $wabaId, bool $withProof = true): array
    {
        $queryParams = [
            'subscribed_fields' => 'messages,phone_number_name_update,phone_number_quality_update,message_template_status_update',
        ];

        if ($appSecret && $withProof) {
            $queryParams['appsecret_proof'] = hash_hmac('sha256', $token, $appSecret);
        }

        Log::info('WhatsApp ManagementClient: subscribeToWebhooks attempt', [
            'trace_id'           => \App\Services\TraceContext::getTraceId(),
            'team_id'            => $this->team?->id,
            'waba_id'            => $wabaId,
            'has_appsecret_proof'=> array_key_exists('appsecret_proof', $queryParams),
        ]);

        $response = Http::withToken($token)->post($url . '?' . http_build_query($queryParams));

        if ($response->failed()) {
            $error = $response->json('error') ?? [];
            $meta  = [
                'code'          => $error['code'] ?? null,
                'error_subcode' => $error['error_subcode'] ?? null,
                'type'          => $error['type'] ?? null,
                'fbtrace_id'    => $error['fbtrace_id'] ?? null,
            ];

            Log::warning('WhatsApp ManagementClient: subscribeToWebhooks failed', [
                'trace_id'   => \App\Services\TraceContext::getTraceId(),
                'team_id'    => $this->team?->id,
                'waba_id'    => $wabaId,
                'app_id'     => $appId,
                'http_status'=> $response->status(),
                'meta'       => $meta,
                'message'    => $error['message'] ?? 'Subscription failed',
            ]);

            return [
                'status'  => false,
                'message' => $error['message'] ?? ($response->json('error.message') ?? 'Subscription failed'),
                'error'   => $error['message'] ?? ($response->json('error.message') ?? 'Subscription failed'),
                'meta'    => $meta,
                'http_status' => $response->status(),
            ];
        }

        Log::info('WhatsApp ManagementClient: subscribeToWebhooks success', [
            'trace_id' => \App\Services\TraceContext::getTraceId(),
            'team_id'  => $this->team?->id,
            'waba_id'  => $wabaId,
            'app_id'   => $appId,
        ]);

        return ['status' => true];
    }

    /**
     * Fetch WABA health/status information.
     */
    public function getWabaStatus(string $wabaId, string $token): array
    {
        $url = 'https://graph.facebook.com/'.config('whatsapp.api_version', 'v21.0')."/{$wabaId}";
        $response = Http::withToken($token)->get($url, [
            'fields' => 'id,name,account_review_status,business_verification_status',
        ]);

        if ($response->failed()) {
            return ['status' => false, 'error' => $response->json('error.message') ?? 'Fetch failed'];
        }

        return ['status' => true, 'data' => $response->json()];
    }
}

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
     * For embedded signup (USER tokens), the appsecret_proof may be rejected because the
     * token's app differs from the configured app secret. We auto-retry without the proof
     * in that case — the subscription POST itself will succeed as long as the token is valid.
     */
    public function subscribeToWebhooks(string $wabaId, string $token): array
    {
        $creds = $this->team ? $this->resolver->resolve($this->team) : null;
        $appId = $creds['app_id'] ?? config('whatsapp.app_id');
        $appSecret = $creds['app_secret'] ?? config('whatsapp.app_secret');

        $version = config('whatsapp.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$wabaId}/subscribed_apps";

        // First attempt (with proof unless explicitly skipped)
        $result = $this->doSubscribePost($url, $token, $appId, $appSecret, $wabaId, withProof: !$this->skipAppSecretProof);

        // Auto-retry without proof if Meta rejected due to invalid appsecret_proof
        if (! ($result['status'] ?? false)) {
            $meta = $result['meta'] ?? [];
            $msg  = $result['message'] ?? '';
            $code = $meta['code'] ?? 0;

            $isProofError = ($code === 100 && str_contains($msg, 'appsecret_proof'))
                         || str_contains($msg, 'Invalid appsecret_proof')
                         || str_contains($msg, '#200')
                         || str_contains($msg, 'Permissions error');

            if ($isProofError) {
                Log::info('WhatsApp ManagementClient: appsecret_proof rejected — retrying without proof', [
                    'trace_id' => \App\Services\TraceContext::getTraceId(),
                    'team_id'  => $this->team?->id,
                    'waba_id'  => $wabaId,
                    'code'     => $code,
                ]);
                $result = $this->doSubscribePost($url, $token, $appId, $appSecret, $wabaId, withProof: false);
            }
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

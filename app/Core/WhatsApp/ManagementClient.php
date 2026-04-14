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
     */
    public function subscribeToWebhooks(string $wabaId, string $token): array
    {
        $creds = $this->team ? $this->resolver->resolve($this->team) : null;
        $appId = $creds['app_id'] ?? config('whatsapp.app_id');
        $appSecret = $creds['app_secret'] ?? config('whatsapp.app_secret');

        $url = 'https://graph.facebook.com/'.config('whatsapp.api_version', 'v21.0')."/{$wabaId}/subscribed_apps";

        $params = [
            'subscribed_fields' => 'messages,phone_number_name_update,phone_number_quality_update,message_template_status_update,template_performance_metrics'
        ];
        
        // Add appsecret_proof for security (unless skipped)
        if ($appSecret && !$this->skipAppSecretProof) {
            $params['appsecret_proof'] = hash_hmac('sha256', $token, $appSecret);
        }

        Log::info('WhatsApp ManagementClient: subscribeToWebhooks request', [
            'trace_id' => \App\Services\TraceContext::getTraceId(),
            'team_id' => $this->team?->id,
            'waba_id' => $wabaId,
            'app_id' => $appId,
            'has_appsecret_proof' => array_key_exists('appsecret_proof', $params),
        ]);

        $response = Http::withToken($token)->post($url, $params);

        if ($response->failed()) {
            $error = $response->json('error') ?? [];
            $meta = [
                'code' => $error['code'] ?? null,
                'error_subcode' => $error['error_subcode'] ?? null,
                'type' => $error['type'] ?? null,
                'fbtrace_id' => $error['fbtrace_id'] ?? null,
            ];

            Log::warning('WhatsApp ManagementClient: subscribeToWebhooks failed', [
                'trace_id' => \App\Services\TraceContext::getTraceId(),
                'team_id' => $this->team?->id,
                'waba_id' => $wabaId,
                'app_id' => $appId,
                'http_status' => $response->status(),
                'meta' => $meta,
                'message' => $error['message'] ?? ($response->json('error.message') ?? 'Subscription failed'),
            ]);

            return [
                'status' => false,
                'message' => $error['message'] ?? ($response->json('error.message') ?? 'Subscription failed'),
                'error' => $error['message'] ?? ($response->json('error.message') ?? 'Subscription failed'),
                'meta' => $meta,
                'http_status' => $response->status(),
            ];
        }

        Log::info('WhatsApp ManagementClient: subscribeToWebhooks success', [
            'trace_id' => \App\Services\TraceContext::getTraceId(),
            'team_id' => $this->team?->id,
            'waba_id' => $wabaId,
            'app_id' => $appId,
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

<?php

namespace App\Core\WhatsApp;

use App\Models\Team;
use Illuminate\Support\Facades\Http;

class ManagementClient
{
    protected WhatsAppClient $client;

    protected CredentialResolver $resolver;
    protected ?Team $team = null;

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
     * Exchange short-lived token for long-lived.
     */
    public function exchangeToken(string $shortLivedToken): array
    {
        $appId = $this->team ? $this->resolver->resolve($this->team)['app_id'] : config('whatsapp.app_id');
        $appSecret = config('whatsapp.app_secret');

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
        $appId = $this->team ? $this->resolver->resolve($this->team)['app_id'] : config('whatsapp.app_id');
        $appSecret = config('whatsapp.app_secret');
        $url = 'https://graph.facebook.com/'.config('whatsapp.api_version', 'v21.0')."/{$wabaId}/subscribed_apps";

        $params = [
            'app_id' => $appId,
            'subscribed_fields' => 'messages,messaging_postbacks,message_echoes,forwarded_messages,message_deliveries,message_reads'
        ];
        
        // Add appsecret_proof for security
        if ($appSecret) {
            $params['appsecret_proof'] = hash_hmac('sha256', $token, $appSecret);
        }

        $response = Http::withToken($token)->post($url, $params);

        if ($response->failed()) {
            return ['status' => false, 'error' => $response->json('error.message') ?? 'Subscription failed'];
        }

        if ($response->failed()) {
            return ['status' => false, 'error' => $response->json('error.message') ?? 'Subscription failed'];
        }

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

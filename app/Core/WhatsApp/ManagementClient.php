<?php

namespace App\Core\WhatsApp;

use App\Models\Team;
use Illuminate\Support\Facades\Http;

class ManagementClient
{
    protected WhatsAppClient $client;

    protected CredentialResolver $resolver;

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
        $this->client->forTeam($team);

        return $this;
    }

    /**
     * Exchange short-lived token for long-lived.
     */
    public function exchangeToken(string $shortLivedToken): array
    {
        $appId = config('whatsapp.app_id');
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

        return ['status' => true, 'access_token' => $response->json('access_token')];
    }

    /**
     * Subscribe to webhooks.
     */
    public function subscribeToWebhooks(string $wabaId, string $token): array
    {
        $appId = config('whatsapp.app_id');
        $url = 'https://graph.facebook.com/'.config('whatsapp.api_version', 'v21.0')."/{$wabaId}/subscribed_apps";

        $response = Http::withToken($token)->post($url, ['app_id' => $appId]);

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

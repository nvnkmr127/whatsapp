<?php

namespace App\Core\WhatsApp;

use App\Models\Team;
use App\Models\WhatsAppFlow;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlowClient
{
    protected WhatsAppClient $client;
    protected CredentialResolver $resolver;

    public function __construct(WhatsAppClient $client, CredentialResolver $resolver)
    {
        $this->client = $client;
        $this->resolver = $resolver;
    }

    /**
     * Create a new Flow on Meta.
     */
    public function createFlow(Team $team, string $name, string $category): array
    {
        $creds = $this->resolver->resolve($team);
        $wabaId = $creds['waba_id'];

        return $this->client->forTeam($team)->sendRequest("{$wabaId}/flows", [
            'name' => $name,
            'categories' => [$category],
        ]);
    }

    /**
     * Upload Flow Assets (JSON structure).
     */
    public function uploadAssets(Team $team, string $flowId, string $jsonContent): array
    {
        $baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com') . '/' . config('whatsapp.api_version', 'v21.0');
        $creds = $this->resolver->resolve($team);
        
        // Multipart upload via Http facade
        $response = Http::withToken((string) $creds['token'])
            ->attach('file', $jsonContent, 'flow.json', ['Content-Type' => 'application/json'])
            ->post("{$baseUrl}/{$flowId}/assets", [
                'name' => 'flow.json',
                'asset_type' => 'FLOW_JSON',
            ]);

        if ($response->failed()) {
            return ['success' => false, 'error' => $response->json('error.message') ?? 'Upload failed'];
        }

        return ['success' => true, 'data' => $response->json()];
    }

    /**
     * Publish the Flow.
     */
    public function publish(Team $team, string $flowId): array
    {
        return $this->client->forTeam($team)->sendRequest("{$flowId}/publish", [], 'post');
    }

    /**
     * Upload Public Key to Meta for Flows.
     */
    public function uploadPublicKey(Team $team, string $publicKey): array
    {
        $creds = $this->resolver->resolve($team);
        $wabaId = $creds['waba_id'];

        return $this->client->forTeam($team)->sendRequest("{$wabaId}/public_key", [
            'public_key' => $publicKey,
        ]);
    }
}

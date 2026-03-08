<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Enums\IntegrationState;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaMarketingService
{
    protected $integration;
    protected $healthService;
    protected $baseUrl = 'https://graph.facebook.com/v21.0';
    protected $accessToken;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
        $this->healthService = app(IntegrationHealthService::class);
        $this->accessToken = $integration->credentials['access_token'] ?? null;
    }

    /**
     * Fetch Ad Accounts associated with the authenticated user.
     */
    public function getAdAccounts()
    {
        return $this->makeRequest('GET', '/me/adaccounts', [
            'fields' => 'id,name,account_status,currency,timezone_name'
        ]);
    }

    /**
     * Fetch Campaigns for a specific Ad Account.
     */
    public function getCampaigns($adAccountId)
    {
        return $this->makeRequest('GET', "/{$adAccountId}/campaigns", [
            'fields' => 'id,name,status,objective,start_time,stop_time,daily_budget,lifetime_budget,spend'
        ]);
    }

    /**
     * Create a new Campaign.
     */
    public function createCampaign($adAccountId, array $data)
    {
        return $this->makeRequest('POST', "/{$adAccountId}/campaigns", $data);
    }

    /**
     * Create an Ad Set.
     */
    public function createAdSet($adAccountId, array $data)
    {
        return $this->makeRequest('POST', "/{$adAccountId}/adsets", $data);
    }

    /**
     * Create an Ad Creative.
     */
    public function createAdCreative($adAccountId, array $data)
    {
        return $this->makeRequest('POST', "/{$adAccountId}/adcreatives", $data);
    }

    /**
     * Create an Ad.
     */
    public function createAd($adAccountId, array $data)
    {
        return $this->makeRequest('POST', "/{$adAccountId}/ads", $data);
    }

    /**
     * Send Conversion Event (CAPI).
     * 
     * @param string $pixelId The Meta Pixel ID to send events to.
     * @param array $events Array of event objects following CAPI structure.
     * @return array
     */
    public function sendConversionEvents($pixelId, array $events)
    {
        // CAPI requires 'data' wrapper and 'access_token' as query param or form field
        // Endpoint: POST /{pixel_id}/events
        
        return $this->makeRequest('POST', "/{$pixelId}/events", [
            'data' => $events,
            // 'test_event_code' => 'TEST12345' // Optional: Add for testing in Events Manager
        ]);
    }

    /**
     * Helper to make authenticated requests to Graph API.
     */
    protected function makeRequest($method, $endpoint, $params = [])
    {
        if (!$this->accessToken) {
            throw new \Exception("Missing Meta Marketing Access Token");
        }

        try {
            $url = "{$this->baseUrl}{$endpoint}";
            
            // Append access token to all requests
            $params['access_token'] = $this->accessToken;

            $response = Http::$method($url, $params);

            if ($response->failed()) {
                $error = $response->json('error.message') ?? $response->body();
                throw new \Exception("Meta API Error: {$error}");
            }

            return $response->json('data') ?? $response->json(); // 'data' is common wrapper, but writes return ID directly

        } catch (\Exception $e) {
            $this->healthService->reportApiError($this->integration, $e);
            throw $e;
        }
    }
}

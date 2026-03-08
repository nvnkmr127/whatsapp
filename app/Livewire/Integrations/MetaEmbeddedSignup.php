<?php

namespace App\Livewire\Integrations;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Integration;
use App\Enums\IntegrationType;
use App\Enums\IntegrationStatus;

class MetaEmbeddedSignup extends Component
{
    public $errorMessage = '';

    public function render()
    {
        return view('livewire.integrations.meta-embedded-signup');
    }

    /**
     * Handle the response from the Facebook JS SDK login.
     * Exchange short-lived token for long-lived token and store integration.
     */
    public function handleAuthResponse($shortLivedToken, $userId)
    {
        $this->errorMessage = '';

        try {
            // 1. Exchange for Long-Lived Token
            $longLivedTokenData = $this->exchangeForLongLivedToken($shortLivedToken);
            
            if (!$longLivedTokenData) {
                $this->errorMessage = 'Failed to exchange token with Meta.';
                return;
            }

            $accessToken = $longLivedTokenData['access_token'];
            $expiresIn = $longLivedTokenData['expires_in'] ?? 5184000; // Default 60 days

            // 2. Fetch User Profile to name the integration
            $profile = $this->fetchUserProfile($accessToken);
            $name = $profile['name'] ?? 'Meta User';
            $email = $profile['email'] ?? null;
            $facebookUserId = $profile['id'] ?? $userId;

            // 3. Create or Update Integration
            // Check if we already have this user integrated for this team
            $existingIntegration = Integration::where('team_id', auth()->user()->currentTeam->id)
                ->where('type', 'meta_marketing') // Use string literal or Enum if available
                ->get()
                ->filter(function ($integration) use ($facebookUserId) {
                    return isset($integration->credentials['user_id']) && 
                           $integration->credentials['user_id'] === $facebookUserId;
                })
                ->first();

            $credentials = [
                'access_token' => $accessToken,
                'user_id' => $facebookUserId,
                'expires_at' => now()->addSeconds($expiresIn)->timestamp,
                'scopes' => $longLivedTokenData['scope'] ?? '', // Store granted scopes
            ];

            if ($existingIntegration) {
                $existingIntegration->update([
                    'name' => "Meta Marketing - $name",
                    'credentials' => $credentials,
                    'status' => 'active', // IntegrationStatus::ACTIVE
                    'updated_at' => now(),
                ]);
                
                $message = "Reconnected Meta account for $name";
            } else {
                Integration::create([
                    'team_id' => auth()->user()->currentTeam->id,
                    'name' => "Meta Marketing - $name",
                    'type' => 'meta_marketing', // IntegrationType::META_MARKETING
                    'credentials' => $credentials,
                    'status' => 'active', // IntegrationStatus::ACTIVE
                    'settings' => ['email' => $email],
                ]);

                $message = "Connected Meta account for $name";
            }

            $this->dispatch('integration-connected', message: $message);
            $this->dispatch('refresh-integrations'); // Event to refresh parent list if needed

        } catch (\Exception $e) {
            Log::error('Meta Embedded Signup Error: ' . $e->getMessage());
            $this->errorMessage = 'An error occurred while connecting. Please try again.';
        }
    }

    /**
     * Exchange short-lived token for long-lived token server-side.
     */
    protected function exchangeForLongLivedToken($shortLivedToken)
    {
        $appId = config('services.facebook.client_id');
        $appSecret = config('services.facebook.client_secret');

        $response = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Meta Token Exchange Failed', ['body' => $response->body()]);
        return null;
    }

    /**
     * Fetch basic user profile.
     */
    protected function fetchUserProfile($accessToken)
    {
        $response = Http::get('https://graph.facebook.com/v21.0/me', [
            'fields' => 'id,name,email',
            'access_token' => $accessToken,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }
}

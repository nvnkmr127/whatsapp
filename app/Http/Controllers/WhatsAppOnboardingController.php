<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppOnboardingController extends Controller
{
    use \App\Traits\WhatsApp;

    /**
     * Exchange short-lived user token for long-lived token.
     */
    public function exchangeToken(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        $shortLivedToken = $request->input('access_token');
        $team = $request->user()->currentTeam; // Define $team early for logging

        try {
            // Use Trait method directly instead of non-existent Service method
            $result = $this->exchangeForLongLivedToken($shortLivedToken);

            if (!$result['status']) {
                $errorMsg = $result['message'] ?? 'Unknown error';
                $referenceId = \App\Models\WhatsAppSetupAudit::generateReferenceId();

                // Log interaction for failed token exchange
                if ($team) {
                    $endpoint = 'token_exchange';
                    $payload = ['short_lived_token_preview' => substr($shortLivedToken, 0, 8) . '...'];
                    \App\Services\WhatsAppEventBridge::logInteraction($team, $endpoint, 'failed', $payload, ['error' => $errorMsg]);
                }

                Log::error('WhatsApp Token Exchange Failed', [
                    'error' => $errorMsg,
                    'reference_id' => $referenceId
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Token Exchange Failed: ' . $errorMsg,
                    'retry_allowed' => true,
                    'reference_id' => $referenceId
                ], 400);
            }

            // Trait returns flat array: ['status' => true, 'access_token' => ..., 'expires_in' => ...]
            $longLivedToken = $result['access_token'] ?? null;
            $expiresIn = $result['expires_in'] ?? 5184000; // 60 days default

            if (!$longLivedToken) {
                // Log interaction for missing token in successful response
                if ($team) {
                    $endpoint = 'token_exchange';
                    $payload = ['short_lived_token_preview' => substr($shortLivedToken, 0, 8) . '...'];
                    \App\Services\WhatsAppEventBridge::logInteraction($team, $endpoint, 'failed', $payload, ['error' => 'No access token received.']);
                }
                return response()->json(['status' => false, 'message' => 'No access token received from Facebook.'], 400);
            }

            // [FIX] Persist Token Immediately
            $team = $request->user()->currentTeam;
            if ($team) {
                $team->forceFill([
                    'whatsapp_access_token' => $longLivedToken,
                    'whatsapp_token_expires_at' => now()->addSeconds($expiresIn),
                ])->save();

                // [NEW] Attempt to find WABA ID automatically if not provided or to verify
                $wabaId = $request->input('waba_id');

                // 1. Try Granular Scopes (System Users / Explicit Grants)
                $debug = $this->debugToken($longLivedToken);
                $foundInScopes = false;
                if ($debug['status'] && !empty($debug['data']['granular_scopes'])) {
                    foreach ($debug['data']['granular_scopes'] as $scope) {
                        if ($scope['scope'] === 'whatsapp_business_management' && !empty($scope['target_ids'])) {
                            $wabaId = $scope['target_ids'][0]; // Take the first WABA ID found
                            $foundInScopes = true;
                            Log::info("WhatsApp Onboarding: Auto-discovered WABA ID {$wabaId} from token scopes.");
                            break;
                        }
                    }
                }

                // 2. Fallback: Fetch Accessible WABA IDs (Standard Users)
                // If the frontend sent a User ID (which is common mistake), or we didn't find one in scopes
                if (!$foundInScopes) {
                    $accessibleWabas = $this->getAccessibleWabaIds($longLivedToken);
                    if (!empty($accessibleWabas)) {
                        // If we have exactly one, use it.
                        // If we have multiple, and the input looks like a user ID (short), pick the first one.
                        // If the input matches one of them, use that.

                        if (in_array($wabaId, $accessibleWabas)) {
                            // The input was actually valid
                        } else {
                            // Input was likely a User ID or null. Pick first.
                            $wabaId = $accessibleWabas[0];
                            Log::info("WhatsApp Onboarding: Discovered WABA ID {$wabaId} from accessible accounts list.");
                        }
                    }
                }

                if ($wabaId) {
                    $team->update(['whatsapp_business_account_id' => $wabaId]);

                    // [NEW] Automatically fetch and store Facebook Business ID
                    $fbBusinessId = $this->getFacebookBusinessId($wabaId, $longLivedToken);
                    if ($fbBusinessId) {
                        $team->update(['facebook_business_id' => $fbBusinessId]);
                        Log::info("WhatsApp Onboarding: Stored Facebook Business ID {$fbBusinessId} for Team {$team->id}");
                    } else {
                        Log::warning("WhatsApp Onboarding: Could not fetch Facebook Business ID for WABA {$wabaId}");
                    }
                }

                \App\Services\WhatsAppEventBridge::auditConfig($team, 'token_exchange', 'completed', [
                    'expires_at' => now()->addSeconds($expiresIn)->toDateTimeString(),
                    'token_preview' => substr($longLivedToken, 0, 8) . '...',
                    'waba_id' => $wabaId,
                    'facebook_business_id' => $fbBusinessId ?? null
                ]);

                Log::info("WhatsApp Token & WABA ID Persisted for Team {$team->id}");
            }

            // 2. Return token with expiration info AND the discovered WABA ID
            return response()->json([
                'status' => true,
                'access_token' => $longLivedToken,
                'waba_id' => $wabaId ?? $team->whatsapp_business_account_id,
                'expires_in' => $expiresIn,
                'expires_at' => now()->addSeconds($expiresIn)->toIso8601String()
            ]);

        } catch (\Exception $e) {
            $referenceId = \App\Models\WhatsAppSetupAudit::generateReferenceId();

            Log::error('WhatsApp Onboarding Exception', [
                'exception' => $e->getMessage(),
                'reference_id' => $referenceId
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Server error during token exchange.',
                'reference_id' => $referenceId
            ], 500);
        }
    }

    /**
     * Convert API error codes to human-readable messages
     */
    private function getHumanReadableError($errorDetails): string
    {
        $code = $errorDetails['error']['code'] ?? null;

        return match ($code) {
            190 => 'Access token expired or invalid. Please reconnect your Facebook account.',
            100 => 'Invalid App ID or Secret. Please check your configuration.',
            102 => 'Session expired. Please try logging in again.',
            default => 'Connection failed: ' . ($errorDetails['error']['message'] ?? 'Unknown error')
        };
    }
}

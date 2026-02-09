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

                // If the JS provided a User ID (usually shorter or different) instead of WABA ID, 
                // or if we just want to be sure, we can fetch it from Meta.
                $debug = $this->debugToken($longLivedToken);
                if ($debug['status'] && !empty($debug['data']['granular_scopes'])) {
                    foreach ($debug['data']['granular_scopes'] as $scope) {
                        if ($scope['scope'] === 'whatsapp_business_management' && !empty($scope['target_ids'])) {
                            $wabaId = $scope['target_ids'][0]; // Take the first WABA ID found
                            Log::info("WhatsApp Onboarding: Auto-discovered WABA ID {$wabaId} from token scopes.");
                            break;
                        }
                    }
                }

                if ($wabaId) {
                    $team->update(['whatsapp_business_account_id' => $wabaId]);
                }

                \App\Services\WhatsAppEventBridge::auditConfig($team, 'token_exchange', 'completed', [
                    'expires_at' => now()->addSeconds($expiresIn)->toDateTimeString(),
                    'token_preview' => substr($longLivedToken, 0, 8) . '...',
                    'waba_id' => $wabaId
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

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

            if (! $result['status']) {
                $errorMsg = $result['message'] ?? 'Unknown error';
                $referenceId = \App\Models\WhatsAppSetupAudit::generateReferenceId();

                // Log interaction for failed token exchange
                if ($team) {
                    $endpoint = 'token_exchange';
                    $payload = ['short_lived_token_preview' => substr($shortLivedToken, 0, 8).'...'];
                    \App\Services\WhatsAppEventBridge::logInteraction($team, $endpoint, 'failed', $payload, ['error' => $errorMsg]);
                }

                // [SECURITY] Mask sensitive info from raw response before logging
                $safeResponse = $result['raw'] ?? null;
                if ($safeResponse) {
                    $safeResponse = preg_replace('/"access_token":\s*"[^"]+"/', '"access_token": "MATCHED_AND_REDACTED"', json_encode($safeResponse));
                }

                Log::error('WhatsApp Token Exchange Failed', [
                    'error' => $errorMsg,
                    'raw_response' => $safeResponse,
                    'reference_id' => $referenceId,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Token Exchange Failed: '.$errorMsg,
                    'retry_allowed' => true,
                    'reference_id' => $referenceId,
                ], 400);
            }

            // Trait returns flat array: ['status' => true, 'access_token' => ..., 'expires_in' => ...]
            $longLivedToken = $result['access_token'] ?? null;
            $expiresIn = $result['expires_in'] ?? 5184000; // 60 days default

            if (! $longLivedToken) {
                // Log interaction for missing token in successful response
                if ($team) {
                    $endpoint = 'token_exchange';
                    $payload = ['short_lived_token_preview' => substr($shortLivedToken, 0, 8).'...'];
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

                // [CRITICAL] Mandatory Scope Validation
                $scopes = $debug['data']['scopes'] ?? [];
                $mandatoryScopes = ['whatsapp_business_messaging', 'whatsapp_business_management'];
                $missingScopes = array_diff($mandatoryScopes, $scopes);

                if (!empty($missingScopes)) {
                    $errorMsg = 'Missing mandatory permissions: ' . implode(', ', $missingScopes) . '. Please ensure you grant all requested permissions in the Facebook popup.';
                    Log::warning("WhatsApp Onboarding: Incomplete permissions for Team {$team->id}", ['missing' => $missingScopes]);
                    
                    return response()->json([
                        'status' => false,
                        'message' => $errorMsg,
                        'retry_allowed' => true,
                    ], 403);
                }

                $foundInScopes = false;
                if ($debug['status'] && ! empty($debug['data']['granular_scopes'])) {
                    foreach ($debug['data']['granular_scopes'] as $scope) {
                        if ($scope['scope'] === 'whatsapp_business_management' && ! empty($scope['target_ids'])) {
                            $wabaId = $scope['target_ids'][0]; // Take the first WABA ID found
                            $foundInScopes = true;
                            Log::info("WhatsApp Onboarding: Auto-discovered WABA ID {$wabaId} from token scopes.");
                            break;
                        }
                    }
                }

                // 2. Fallback: Fetch Accessible WABA IDs (Standard Users)
                // If the frontend sent a User ID (which is common mistake), or we didn't find one in scopes
                $wabaOptions = [];
                if (! $foundInScopes) {
                    $accessibleWabas = $this->getAccessibleWabaIds($longLivedToken);
                    if (! empty($accessibleWabas)) {
                        if (count($accessibleWabas) === 1) {
                            $wabaId = $accessibleWabas[0];
                        } else {
                            // Multiple WABAs found. We should return them as options.
                            foreach ($accessibleWabas as $optId) {
                                // Fetch name for each WABA to make selection easier
                                $wabaInfo = $this->mgmt()->getWabaStatus($optId, $longLivedToken);
                                $wabaOptions[] = [
                                    'id' => $optId,
                                    'name' => $wabaInfo['data']['name'] ?? 'WABA '.$optId,
                                    'status' => strtoupper($wabaInfo['data']['account_review_status'] ?? 'unknown'),
                                    'verification' => strtoupper($wabaInfo['data']['business_verification_status'] ?? 'unknown'),
                                ];
                            }
                        }
                    }
                }

                if ($wabaId || $longLivedToken) {
                    // [SAFEGUARD] Prevent accidental account switching if already connected
                    if ($team->whatsapp_connected && !empty($team->whatsapp_business_account_id) && $wabaId && $team->whatsapp_business_account_id !== $wabaId) {
                        Log::warning("WhatsApp Onboarding: Blocked account switch attempt for Team {$team->id}. Old: {$team->whatsapp_business_account_id}, New: {$wabaId}");
                        return response()->json([
                            'status' => false,
                            'message' => 'This team is already linked to a different WhatsApp Account. Please disconnect the current account before switching.',
                        ], 409); // Conflict
                    }

                    $fbBusinessId = $wabaId ? $this->getFacebookBusinessId($wabaId, $longLivedToken) : null;
                    
                    $team->forceFill([
                        'whatsapp_access_token' => $longLivedToken,
                        'whatsapp_business_account_id' => $wabaId ?? $team->whatsapp_business_account_id,
                        'whatsapp_setup_state' => \App\Enums\IntegrationState::READY,
                        'whatsapp_token_expires_at' => now()->addSeconds($expiresIn),
                        'facebook_business_id' => $fbBusinessId,
                    ])->save();

                    \App\Services\WhatsAppEventBridge::auditConfig($team, 'token_exchange', 'completed', [
                        'expires_at' => now()->addSeconds($expiresIn)->toDateTimeString(),
                        'waba_id' => $wabaId,
                    ]);
                }

                Log::info("WhatsApp Token & WABA ID Persisted for Team {$team->id}");
            }

            $referenceId = \App\Models\WhatsAppSetupAudit::generateReferenceId();

            // 2. Return token with expiration info AND the discovered WABA ID
            return response()->json([
                'status' => true,
                'access_token' => $longLivedToken,
                'waba_id' => $wabaId ?? $team->whatsapp_business_account_id,
                'waba_options' => $wabaOptions,
                'expires_in' => $expiresIn,
                'expires_at' => now()->addSeconds($expiresIn)->toIso8601String(),
                'reference_id' => $referenceId,
            ]);

        } catch (\Exception $e) {
            $referenceId = \App\Models\WhatsAppSetupAudit::generateReferenceId();

            Log::error('WhatsApp Onboarding Exception', [
                'exception' => $e->getMessage(),
                'reference_id' => $referenceId,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Server error during token exchange.',
                'reference_id' => $referenceId,
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
            default => 'Connection failed: '.($errorDetails['error']['message'] ?? 'Unknown error')
        };
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppOnboardingController extends Controller
{
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
            $whatsappService = app(\App\Services\WhatsAppService::class);
            $result = $whatsappService->exchangeToken($shortLivedToken);

            if (!$result['success']) {
                $errorDetails = $result['error'];
                $referenceId = \App\Models\WhatsAppSetupAudit::generateReferenceId();

                // Log interaction for failed token exchange
                if ($team) {
                    $endpoint = 'token_exchange'; // Assuming this is the endpoint for logging
                    $payload = ['short_lived_token_preview' => substr($shortLivedToken, 0, 8) . '...'];
                    \App\Services\WhatsAppEventBridge::logInteraction($team, $endpoint, 'failed', $payload, ['error' => $errorDetails]);
                }

                Log::error('WhatsApp Token Exchange Failed', [
                    'error' => $errorDetails,
                    'reference_id' => $referenceId
                ]);

                $humanMessage = $this->getHumanReadableError($errorDetails);

                return response()->json([
                    'status' => false,
                    'message' => $humanMessage,
                    'retry_allowed' => true,
                    'reference_id' => $referenceId
                ], $result['status_code'] ?? 400);
            }

            $data = $result['data'];
            $longLivedToken = $data['access_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? 5184000; // 60 days default

            if (!$longLivedToken) {
                // Log interaction for missing token in successful response
                if ($team) {
                    $endpoint = 'token_exchange';
                    $payload = ['short_lived_token_preview' => substr($shortLivedToken, 0, 8) . '...'];
                    \App\Services\WhatsAppEventBridge::logInteraction($team, $endpoint, 'failed', $payload, ['error' => 'No access token received from Facebook.']);
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

                \App\Services\WhatsAppEventBridge::auditConfig($team, 'token_exchange', 'completed', [
                    'expires_at' => now()->addSeconds($expiresIn)->toDateTimeString(),
                    'token_preview' => substr($longLivedToken, 0, 8) . '...'
                ]);

                Log::info("WhatsApp Token Persisted for Team {$team->id}");
            }

            // 2. Return token with expiration info
            return response()->json([
                'status' => true,
                'access_token' => $longLivedToken, // Optional to return since we saved it
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

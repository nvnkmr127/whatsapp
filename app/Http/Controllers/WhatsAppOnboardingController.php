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

        try {
            $whatsappService = app(\App\Services\WhatsAppService::class);
            $result = $whatsappService->exchangeToken($shortLivedToken);

            if (!$result['success']) {
                $errorDetails = $result['error'];
                $referenceId = \App\Models\WhatsAppSetupAudit::generateReferenceId();

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
                return response()->json(['status' => false, 'message' => 'No access token received from Facebook.'], 400);
            }

            // 2. Return token with expiration info
            return response()->json([
                'status' => true,
                'access_token' => $longLivedToken,
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

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
        $appId = config('services.facebook.client_id');
        $appSecret = config('services.facebook.client_secret');

        if (!$appId || !$appSecret) {
            return response()->json(['status' => false, 'message' => 'Facebook App configuration missing on server.'], 500);
        }

        try {
            // 1. Exchange for Long-Lived Token
            $response = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $shortLivedToken,
            ]);

            if ($response->failed()) {
                Log::error('WhatsApp Token Exchange Failed', ['error' => $response->json()]);
                return response()->json(['status' => false, 'message' => 'Failed to exchange token with Facebook.'], 400);
            }

            $data = $response->json();
            $longLivedToken = $data['access_token'] ?? null;

            if (!$longLivedToken) {
                return response()->json(['status' => false, 'message' => 'No access token received from Facebook.'], 400);
            }

            // 2. (Optional) Fetch WABA info here if needed, or just return token for the component to handle.
            // For now, we return the token so the Livewire component can save it and sync.

            return response()->json([
                'status' => true,
                'access_token' => $longLivedToken,
                // 'expires_in' => $data['expires_in'] ?? null // usually 60 days
            ]);

        } catch (\Exception $e) {
            Log::error('WhatsApp Onboarding Exception', ['exception' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error during token exchange.'], 500);
        }
    }
}

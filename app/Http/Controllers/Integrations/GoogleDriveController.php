<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class GoogleDriveController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirect(Request $request)
    {
        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('integrations.google-drive.callback'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file email profile',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => csrf_token(),
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    /**
     * Handle the OAuth callback.
     */
    public function callback(Request $request)
    {
        if ($request->error) {
            return redirect()->route('settings.integrations')
                ->with('error', 'Google Drive connection failed: ' . $request->error);
        }

        try {
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'code' => $request->code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('integrations.google-drive.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if (!$response->successful()) {
                throw new Exception("Failed to exchange code for token: " . $response->body());
            }

            $data = $response->json();
            $teamId = auth()->user()->current_team_id;

            // Get user info to display which account is connected
            $userInfo = Http::withToken($data['access_token'])
                ->get('https://www.googleapis.com/oauth2/v3/userinfo')
                ->json();

            Integration::updateOrCreate(
                [
                    'team_id' => $teamId,
                    'type' => 'google_drive',
                ],
                [
                    'name' => 'Google Drive (' . ($userInfo['email'] ?? 'Unknown') . ')',
                    'credentials' => [
                        'access_token' => $data['access_token'],
                        'refresh_token' => $data['refresh_token'] ?? null,
                        'expires_in' => $data['expires_in'],
                    ],
                    'status' => 'active',
                    'error_message' => null,
                ]
            );

            return redirect()->route('settings.integrations')
                ->with('success', 'Google Drive connected successfully!');

        } catch (Exception $e) {
            return redirect()->route('settings.integrations')
                ->with('error', 'Error connecting Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect the integration.
     */
    public function disconnect()
    {
        Integration::where('team_id', auth()->user()->current_team_id)
            ->where('type', 'google_drive')
            ->delete();

        return redirect()->back()->with('success', 'Google Drive disconnected.');
    }
}

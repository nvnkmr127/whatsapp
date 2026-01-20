<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;

class GoogleDriveService
{
    protected $integration;

    public function __construct(Integration $integration)
    {
        if ($integration->type !== 'google_drive') {
            throw new Exception("Invalid integration type for GoogleDriveService.");
        }
        $this->integration = $integration;
    }

    /**
     * Upload a file to the tenant's Google Drive.
     */
    public function uploadFile($filePath, $filename)
    {
        $this->refreshTokenIfNeeded();

        $folderId = $this->getOrCreateBackupFolder();

        $content = file_get_contents($filePath);
        $mimeType = 'application/zip';

        // Multipart upload for metadata + file content
        $response = Http::withToken($this->integration->credentials['access_token'])
            ->attach('metadata', json_encode([
                'name' => $filename,
                'parents' => [$folderId]
            ]), 'metadata.json', ['Content-Type' => 'application/json'])
            ->attach('file', $content, $filename, ['Content-Type' => $mimeType])
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

        if (!$response->successful()) {
            throw new Exception("Google Drive upload failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Ensure the backup folder exists and return its ID.
     */
    protected function getOrCreateBackupFolder()
    {
        $settings = $this->integration->settings ?? [];

        if (!empty($settings['google_folder_id'])) {
            // Verify it still exists
            $response = Http::withToken($this->integration->credentials['access_token'])
                ->get("https://www.googleapis.com/drive/v3/files/{$settings['google_folder_id']}?fields=id,trashed");

            if ($response->successful() && !$response->json('trashed')) {
                return $settings['google_folder_id'];
            }
        }

        // Search for existing folder
        $query = "name = 'App Backups' and mimeType = 'application/vnd.google-apps.folder' and 'root' in parents and trashed = false";
        $searchResponse = Http::withToken($this->integration->credentials['access_token'])
            ->get("https://www.googleapis.com/drive/v3/files", [
                'q' => $query,
                'fields' => 'files(id)'
            ]);

        if ($searchResponse->successful() && !empty($searchResponse->json('files'))) {
            $folderId = $searchResponse->json('files.0.id');
        } else {
            // Create new folder
            $createResponse = Http::withToken($this->integration->credentials['access_token'])
                ->post('https://www.googleapis.com/drive/v3/files', [
                    'name' => 'App Backups',
                    'mimeType' => 'application/vnd.google-apps.folder'
                ]);

            if (!$createResponse->successful()) {
                throw new Exception("Failed to create Google Drive folder: " . $createResponse->body());
            }

            $folderId = $createResponse->json('id');
        }

        // Save folder ID to settings
        $settings['google_folder_id'] = $folderId;
        $this->integration->update(['settings' => $settings]);

        return $folderId;
    }

    /**
     * Refresh the access token if it's expired or near expiry.
     */
    protected function refreshTokenIfNeeded()
    {
        $credentials = $this->integration->credentials;
        $expiresAt = $this->integration->updated_at->addSeconds($credentials['expires_in'] ?? 3600);

        if ($expiresAt->isPast() || $expiresAt->diffInMinutes(now()) < 5) {
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $credentials['refresh_token'],
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                $this->integration->update([
                    'status' => 'error',
                    'error_message' => 'Failed to refresh Google Drive token.'
                ]);
                throw new Exception("Google token refresh failed: " . $response->body());
            }

            $data = $response->json();
            $credentials['access_token'] = $data['access_token'];
            $credentials['expires_in'] = $data['expires_in'];

            $this->integration->update([
                'credentials' => $credentials,
                'status' => 'active'
            ]);
        }
    }
}

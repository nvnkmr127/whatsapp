<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com') . '/' . config('whatsapp.api_version', 'v21.0');
    }

    /**
     * Download media from WhatsApp API and store locally.
     * Returns the relative path to the stored file.
     */
    public function downloadAndStore(string $mediaId, Team $team): ?string
    {
        $accessToken = $team->whatsapp_access_token;

        if (!$accessToken) {
            Log::error("Media download failed: No access token for Team {$team->id}");
            return null;
        }

        // 1. Get Media URL
        $response = Http::withToken($accessToken)->get("{$this->baseUrl}/{$mediaId}");

        if ($response->failed()) {
            Log::error("Failed to get media URL for ID {$mediaId}", $response->json());
            return null;
        }

        $mediaUrl = $response->json()['url'] ?? null;
        $mimeType = $response->json()['mime_type'] ?? 'application/octet-stream';

        if (!$mediaUrl) {
            return null;
        }

        // 2. Download Binary
        $binaryResponse = Http::withToken($accessToken)->get($mediaUrl);

        if ($binaryResponse->failed()) {
            Log::error("Failed to download media binary from {$mediaUrl}");
            return null;
        }

        // 3. Determine Extension & Filename
        $extension = $this->guessExtension($mimeType);
        $filename = Str::random(40) . '.' . $extension;
        $path = "whatsapp/{$team->id}/{$filename}";

        // 4. Store
        Storage::disk('public')->put($path, $binaryResponse->body());

        // Return public URL or relative path? 
        // Returning relative path is safer, can wrap with Storage::url() in UI.
        return $path;
    }

    protected function guessExtension($mime)
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'video/mp4' => 'mp4',
            'application/pdf' => 'pdf',
        ];

        return $map[$mime] ?? 'bin';
    }
}

<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /** Meta's CDN rejects unrecognised clients; keep this browser-shaped. */
    public const USER_AGENT = 'Mozilla/5.0 (compatible; WhatsAppBusinessAPI/1.0)';

    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com').'/'.config('whatsapp.api_version', 'v21.0');
    }

    /**
     * Download media from WhatsApp API and store locally.
     * Returns the relative path to the stored file.
     */
    public function downloadAndStore(string $mediaId, Team $team): ?string
    {
        // Every `return null` below is a distinct failure. They are logged with a
        // `step` so the log alone identifies which one hit — DownloadMediaJob only
        // sees "no path returned".
        $log = ['media_id' => $mediaId, 'team_id' => $team->id];

        $accessToken = $team->whatsapp_access_token;
        if (! $accessToken) {
            $accessToken = config('whatsapp.system_access_token') ?: env('WHATSAPP_ACCESS_TOKEN');
        }
        if (! $accessToken) {
            $accessToken = \App\Models\Team::whereNotNull('whatsapp_access_token')
                ->where('whatsapp_access_token', '!=', '')
                ->value('whatsapp_access_token');
        }

        if (! $accessToken) {
            Log::error('Media download failed', $log + ['step' => 'no_access_token', 'reason' => 'No Meta access token found on team, system config, or env']);

            return null;
        }

        Log::info('[MEDIA_SERVICE] Attempting Meta media lookup', $log + [
            'token_source' => $team->whatsapp_access_token ? 'team' : (config('whatsapp.system_access_token') || env('WHATSAPP_ACCESS_TOKEN') ? 'system/env' : 'fallback'),
        ]);

        // 1. Get Media URL
        $response = Http::withToken($accessToken)->get("{$this->baseUrl}/{$mediaId}");

        if ($response->failed()) {
            $code = (int) ($response->json('error.code') ?? 0);
            $isRateLimit = $code === 4 || str_contains($response->body(), 'Application request limit reached') || str_contains($response->body(), 'request limit reached');
            $isTokenExpired = in_array($code, [190, 102]) || str_contains($response->body(), 'Invalid OAuth access token') || str_contains($response->body(), 'Session has expired');

            $step = $isRateLimit ? 'rate_limit_exceeded' : ($isTokenExpired ? 'token_expired' : 'lookup_failed');
            $reason = $isRateLimit
                ? 'Meta API Rate Limit Reached (Code 4 - Transient). Please wait 5 minutes before retrying.'
                : ($isTokenExpired ? 'Meta Access Token is invalid or expired (OAuth Error 190). Please re-authenticate.' : 'Meta Media Lookup Failed');

            Log::error('Media download failed', $log + [
                'step' => $step,
                'is_rate_limit' => $isRateLimit,
                'is_token_expired' => $isTokenExpired,
                'reason' => $reason,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        $mediaUrl = $response->json()['url'] ?? null;
        $mimeType = $response->json()['mime_type'] ?? 'application/octet-stream';

        if (! $mediaUrl) {
            Log::error('Media download failed', $log + [
                'step' => 'lookup_returned_no_url',
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        // 2. Download Binary
        // Meta CDN URLs (lookaside.fbsbx.com) are pre-signed via query parameters.
        // Sending an Authorization header to lookaside CDN causes 500 Internal Server Error upon redirect to Meta edge servers.
        $binaryResponse = Http::withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => '*/*'])
            ->timeout(60)
            ->get($mediaUrl);

        // Fallback: If clean fetch fails, try with Bearer token header
        if ($binaryResponse->failed()) {
            $fallbackResponse = Http::withToken($accessToken)
                ->withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => '*/*'])
                ->timeout(60)
                ->get($mediaUrl);

            if ($fallbackResponse->successful()) {
                $binaryResponse = $fallbackResponse;
            }
        }

        if ($binaryResponse->failed()) {
            Log::error('Media download failed', $log + [
                'step' => 'binary_download_failed',
                'status' => $binaryResponse->status(),
                'body' => Str::limit($binaryResponse->body(), 500),
                'host' => parse_url($mediaUrl, PHP_URL_HOST),
                'response_headers' => array_map(
                    fn ($v) => is_array($v) ? implode(', ', $v) : $v,
                    array_intersect_key($binaryResponse->headers(), array_flip(['Content-Type', 'WWW-Authenticate', 'x-fb-debug', 'x-fb-rev']))
                ),
            ]);

            return null;
        }

        // 3. Determine Extension & Filename
        $extension = $this->guessExtension($mimeType);
        $filename = Str::random(40).'.'.$extension;
        $path = "whatsapp/{$team->id}/{$filename}";

        // 4. Store. Web files MUST be written to public disk so they can be served.
        $configuredDisk = config('filesystems.default', 'public');
        $disk = ($configuredDisk === 'local') ? 'public' : $configuredDisk;

        try {
            // Visibility is left to the disk config. Forcing 'public' here sends an
            // ACL that R2 does not implement, which fails the write on that driver.
            $stored = Storage::disk($disk)->put($path, $binaryResponse->body());
        } catch (\Throwable $e) {
            Log::error('Media download failed', $log + [
                'step' => 'disk_write_threw',
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $stored) {
            Log::error('Media download failed', $log + [
                'step' => 'disk_write_returned_false',
                'disk' => $disk,
                'path' => $path,
                'bytes' => strlen($binaryResponse->body()),
            ]);

            return null;
        }

        Log::info('Media stored', $log + ['disk' => $disk, 'path' => $path, 'bytes' => strlen($binaryResponse->body())]);

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

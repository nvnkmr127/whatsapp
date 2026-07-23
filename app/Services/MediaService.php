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
            Log::error('Media download failed', $log + ['step' => 'no_access_token']);

            return null;
        }

        // 1. Get Media URL
        $response = Http::withToken($accessToken)->get("{$this->baseUrl}/{$mediaId}");

        if ($response->failed()) {
            Log::error('Media download failed', $log + [
                'step' => 'lookup_failed',
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
        // Meta's media URLs are short-lived — if the queue is backed up, this is
        // where a delayed job dies.
        //
        // The User-Agent is required, not cosmetic: Meta's lookaside CDN answers
        // 500 Internal Server Error to clients it doesn't recognise, and Guzzle's
        // default "GuzzleHttp/7" is one of them. See `php artisan media:diagnose
        // --media-id=<id>`, which probes this exact difference.
        $binaryResponse = Http::withToken($accessToken)
            ->withHeaders(['User-Agent' => self::USER_AGENT])
            ->timeout(60)
            ->get($mediaUrl);

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

        // 4. Store. No makeDirectory(): object stores (S3/R2) have no directories,
        // and the local driver creates them on write anyway — it was two wasted
        // round-trips that could themselves throw.
        $disk = config('filesystems.default', 'public');

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

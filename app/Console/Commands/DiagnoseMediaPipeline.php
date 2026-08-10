<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\Team;
use App\Services\MediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Inbound media arrives as a media_id; DownloadMediaJob fetches it from Meta and
 * writes it to the configured disk. When a bubble is stuck on "downloading…" this
 * walks the same path and reports which step is broken.
 */
class DiagnoseMediaPipeline extends Command
{
    protected $signature = 'media:diagnose
        {--team= : Team id (defaults to the first team)}
        {--media-id= : Run the real two-step download against this media id and compare request shapes}';

    protected $description = 'Check the inbound media pipeline: disk config, writability, public URL reachability, and stuck messages';

    public function handle(): int
    {
        $team = $this->option('team')
            ? Team::find($this->option('team'))
            : Team::first();

        if (! $team) {
            $this->error('No team found.');

            return self::FAILURE;
        }

        $ok = true;
        $configuredDisk = config('filesystems.default', 'public');
        $disk = ($configuredDisk === 'local') ? 'public' : $configuredDisk;

        $this->info("Team #{$team->id}  ·  disk: {$disk}  ·  driver: ".config("filesystems.disks.{$disk}.driver"));
        $this->newLine();

        $ok = $this->checkAccessToken($team) && $ok;
        $ok = $this->checkDiskWritable($disk) && $ok;
        $ok = $this->checkPublicUrl($disk) && $ok;

        if ($this->option('media-id')) {
            $ok = $this->probeLiveDownload($team, $this->option('media-id')) && $ok;
        }

        $this->reportStuckMessages($team);

        $this->newLine();
        $this->line($ok ? '<info>Pipeline looks healthy.</info>' : '<error>Pipeline has problems — see above.</error>');

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function checkAccessToken(Team $team): bool
    {
        $token = $team->whatsapp_access_token
            ?: (config('whatsapp.system_access_token') ?: env('WHATSAPP_ACCESS_TOKEN'))
            ?: Team::whereNotNull('whatsapp_access_token')->where('whatsapp_access_token', '!=', '')->value('whatsapp_access_token');

        if (! $token) {
            $this->error('✗ access token: missing — every download fails at step one');

            return false;
        }

        $this->line('<info>✓</info> access token: present');

        return true;
    }

    private function checkDiskWritable(string $disk): bool
    {
        $probe = 'whatsapp/_diagnose/'.uniqid().'.txt';

        try {
            if (! Storage::disk($disk)->put($probe, 'probe')) {
                $this->error("✗ disk write: put() returned false on [{$disk}]");

                return false;
            }
        } catch (\Throwable $e) {
            $this->error("✗ disk write: threw on [{$disk}] — ".$e->getMessage());

            return false;
        }

        $this->line("<info>✓</info> disk write: ok on [{$disk}]");
        Storage::disk($disk)->delete($probe);

        return true;
    }

    /**
     * The download can succeed and the chat still show nothing, if the URL the UI
     * builds does not actually resolve to the file that was written.
     */
    private function checkPublicUrl(string $disk): bool
    {
        $driver = config("filesystems.disks.{$disk}.driver");
        $root = config("filesystems.disks.{$disk}.root");

        try {
            $url = Storage::disk($disk)->url('whatsapp/1/example.jpg');
        } catch (\Throwable $e) {
            $this->error("✗ public url: disk [{$disk}] cannot build URLs — ".$e->getMessage());

            return false;
        }

        // The local driver serves /storage from storage/app/public via the symlink.
        // Any other root is written somewhere the web server will not serve.
        if ($driver === 'local' && ! str_ends_with(rtrim((string) $root, '/'), 'app/public')) {
            $this->error("✗ public url: builds {$url}, but files are written to {$root}");
            $this->line("    /storage maps to storage/app/public, so every media URL 404s.");
            $this->line('    Fix: set FILESYSTEM_DISK=public (or r2), not "local".');

            return false;
        }

        $this->line("<info>✓</info> public url: {$url}");

        return true;
    }

    /**
     * Reproduces DownloadMediaJob's two HTTP calls against a real media id, then
     * retries the binary fetch under different request shapes. A 500 from the CDN
     * that clears with one header change is a client problem, not Meta being down.
     */
    private function probeLiveDownload(Team $team, string $mediaId): bool
    {
        $this->newLine();
        $this->info("Live download probe · media_id={$mediaId}");

        $base = config('whatsapp.base_url', 'https://graph.facebook.com').'/'.config('whatsapp.api_version', 'v21.0');

        $lookup = Http::withToken($team->whatsapp_access_token)->get("{$base}/{$mediaId}");

        if ($lookup->failed()) {
            $this->error("  ✗ step 1 lookup: HTTP {$lookup->status()} — ".Str::limit($lookup->body(), 300));
            $this->line('    The media id is wrong, expired (media is kept ~30 days), or the token lacks access.');

            return false;
        }

        $url = $lookup->json('url');
        $this->line("  <info>✓</info> step 1 lookup: HTTP 200 · {$lookup->json('mime_type')} · ".($lookup->json('file_size') ?? '?').' bytes');

        if (! $url) {
            $this->error('  ✗ step 1 returned no url');

            return false;
        }

        $this->line('    host: '.parse_url($url, PHP_URL_HOST));

        $shapes = [
            'access_token in query string (clean GET + User-Agent)' => 'query_token_step',
            'token + allow_redirects=false -> clean CDN GET' => 'redirect_step',
            'clean GET (no Auth header, pre-signed CDN URL + User-Agent)' => ['headers' => ['User-Agent' => MediaService::USER_AGENT], 'with_token' => false],
            'token + User-Agent' => ['headers' => ['User-Agent' => MediaService::USER_AGENT], 'with_token' => true],
            'token only' => ['headers' => [], 'with_token' => true],
        ];

        $anyWorked = false;

        foreach ($shapes as $label => $config) {
            try {
                if ($config === 'query_token_step') {
                    $token = $team->whatsapp_access_token
                        ?: (config('whatsapp.system_access_token') ?: env('WHATSAPP_ACCESS_TOKEN'))
                        ?: Team::whereNotNull('whatsapp_access_token')->where('whatsapp_access_token', '!=', '')->value('whatsapp_access_token');
                    $sep = str_contains($url, '?') ? '&' : '?';
                    $urlWithToken = $url . $sep . 'access_token=' . urlencode((string) $token);
                    $res = Http::withHeaders(['User-Agent' => MediaService::USER_AGENT, 'Accept' => '*/*'])
                        ->timeout(60)
                        ->get($urlWithToken);
                } elseif ($config === 'redirect_step') {
                    $init = Http::withToken($team->whatsapp_access_token)
                        ->withHeaders(['User-Agent' => MediaService::USER_AGENT, 'Accept' => '*/*'])
                        ->withOptions(['allow_redirects' => false])
                        ->timeout(60)
                        ->get($url);

                    $this->line("    [redirect_step init: HTTP {$init->status()}, Location: " . ($init->header('Location') ?: 'none') . "]");

                    if ($init->redirect() && ($cdnUrl = $init->header('Location'))) {
                        $res = Http::withHeaders(['User-Agent' => MediaService::USER_AGENT, 'Accept' => '*/*'])
                            ->timeout(60)
                            ->get($cdnUrl);
                        $this->line("    [redirect_step cdn: HTTP {$res->status()}]");
                    } else {
                        $res = $init;
                    }
                } else {
                    $req = $config['with_token']
                        ? Http::withToken($team->whatsapp_access_token)->withHeaders($config['headers'])
                        : Http::withHeaders($config['headers']);

                    $res = $req->timeout(60)->get($url);
                }

                $size = strlen($res->body());

                if ($res->successful()) {
                    $anyWorked = true;
                    $this->line("  <info>✓</info> {$label}: HTTP {$res->status()} · {$size} bytes");
                } else {
                    $this->line("  <fg=red>✗</> {$label}: HTTP {$res->status()} · ".Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($res->body()))), 150));
                }
            } catch (\Throwable $e) {
                $this->line("  <fg=red>✗</> {$label}: threw — ".Str::limit($e->getMessage(), 120));
            }
        }

        $this->newLine();
        $this->line($anyWorked
            ? '  At least one shape works — the download is fixable from our side.'
            : '  <comment>No shape worked. The URL may have expired (they are short-lived: re-run with a fresh media id), or Meta is genuinely erroring.</comment>');

        return $anyWorked;
    }

    private function reportStuckMessages(Team $team): void
    {
        $stuck = Message::where('team_id', $team->id)
            ->where('direction', 'inbound')
            ->whereIn('type', ['image', 'video', 'audio', 'document', 'sticker'])
            ->whereNull('media_url')
            ->latest('id')
            ->limit(10)
            ->get(['id', 'type', 'media_id', 'created_at', 'metadata']);

        $this->newLine();

        if ($stuck->isEmpty()) {
            $this->line('<info>✓</info> no messages stuck without media');

            return;
        }

        $this->warn("{$stuck->count()} recent message(s) stuck without media:");

        $this->table(
            ['id', 'type', 'media_id', 'received', 'gave up?'],
            $stuck->map(fn ($m) => [
                $m->id,
                $m->type,
                $m->media_id ?: '(none)',
                $m->created_at?->diffForHumans(),
                ($m->metadata['media_failed'] ?? false) ? 'yes — '.($m->metadata['media_failed_reason'] ?? '') : 'no, still retrying or never ran',
            ])->all()
        );

        $this->line('  "no, still retrying or never ran" on an old message means DownloadMediaJob');
        $this->line('  never reached a worker — check that the queue is being consumed.');
    }
}

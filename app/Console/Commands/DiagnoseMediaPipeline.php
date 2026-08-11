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
        $ok = $this->checkTokenGrants($team) && $ok;
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
            ?: (config('whatsapp.system_access_token') ?: env('WHATSAPP_ACCESS_TOKEN'));

        if (! $token) {
            $this->error("✗ access token: missing for Team #{$team->id} — every download fails at step one");

            return false;
        }

        $this->line('<info>✓</info> access token: present');

        return true;
    }

    /**
     * Metadata reads (GET /{media-id}) work at a lower access tier than the actual
     * blob fetch from lookaside, so a token can be "present" and valid for lookup
     * yet 500 every download. These are the two grants that cause "metadata 200,
     * binary 500": a token missing whatsapp_business_messaging / from the wrong
     * app, or a WABA no app is subscribed to.
     */
    private function checkTokenGrants(Team $team): bool
    {
        $token = $team->whatsapp_access_token
            ?: (config('whatsapp.system_access_token') ?: env('WHATSAPP_ACCESS_TOKEN'));

        $appId = config('whatsapp.app_id');
        $appSecret = config('whatsapp.app_secret');
        $graph = config('whatsapp.base_url', 'https://graph.facebook.com');
        $base = $graph.'/'.config('whatsapp.api_version', 'v21.0');

        $ok = true;

        // 1. Token: is it valid, from our app, and does it carry the media scope?
        if ($appId && $appSecret) {
            $debug = Http::get("{$graph}/debug_token", [
                'input_token' => $token,
                'access_token' => "{$appId}|{$appSecret}",
            ])->json('data') ?? [];
            $scopes = $debug['scopes'] ?? [];

            if (! ($debug['is_valid'] ?? false)) {
                $this->error('✗ token: debug_token reports INVALID — re-authenticate this team.');
                $ok = false;
            } elseif ((string) ($debug['app_id'] ?? '') !== (string) $appId) {
                $this->error("✗ token: issued for app {$debug['app_id']}, but this server is app {$appId} — media download will 500.");
                $ok = false;
            } elseif (! in_array('whatsapp_business_messaging', $scopes, true)) {
                $this->error('✗ token: missing scope `whatsapp_business_messaging` — metadata works but the media blob fetch 500s. Grant Advanced Access to this permission.');
                $ok = false;
            } else {
                $this->line('<info>✓</info> token grants: valid · app '.$debug['app_id'].' · has whatsapp_business_messaging');
            }
        } else {
            $this->warn('  (skipped token scope check: whatsapp.app_id / app_secret not configured)');
        }

        // 2. WABA: is any app subscribed? An empty list means inbound media 500s.
        if ($team->whatsapp_business_account_id) {
            $subs = Http::withToken($token)
                ->get("{$base}/{$team->whatsapp_business_account_id}/subscribed_apps")
                ->json('data');

            if (is_array($subs) && count($subs) === 0) {
                $this->error('✗ WABA subscription: no app subscribed to this WABA — re-run webhook subscription.');
                $ok = false;
            } elseif (is_array($subs)) {
                $this->line('<info>✓</info> WABA subscription: '.count($subs).' app(s) subscribed');
            }
        }

        return $ok;
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
     * Reproduces DownloadMediaJob's two HTTP calls against a real media id. If the
     * documented download fails, the status says whether it is us or Meta.
     */
    private function probeLiveDownload(Team $team, string $mediaId): bool
    {
        $this->newLine();
        $this->info("Live download probe · media_id={$mediaId}");

        $token = $team->whatsapp_access_token
            ?: (config('whatsapp.system_access_token') ?: env('WHATSAPP_ACCESS_TOKEN'));

        $base = config('whatsapp.base_url', 'https://graph.facebook.com').'/'.config('whatsapp.api_version', 'v21.0');

        $lookup = Http::withToken($token)->get("{$base}/{$mediaId}");

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

        // Step 2: the documented download — Bearer token, Guzzle follows the 302 to the CDN.
        $res = Http::withToken($token)
            ->withHeaders(['User-Agent' => MediaService::USER_AGENT])
            ->timeout(60)
            ->get($url);

        if ($res->successful()) {
            $this->line("  <info>✓</info> step 2 download: HTTP {$res->status()} · ".strlen($res->body()).' bytes');

            return true;
        }

        $this->error("  ✗ step 2 download: HTTP {$res->status()} · ".Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($res->body()))), 150));
        $this->line(in_array($res->status(), [401, 403], true)
            ? '    Token is not authorized to download this media — check the WABA is subscribed to this app.'
            : '    Meta returned a server error on a valid token/URL — retry, or the media object is broken on Meta’s side.');

        return false;
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

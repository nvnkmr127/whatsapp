<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Inbound media arrives as a media_id; DownloadMediaJob fetches it from Meta and
 * writes it to the configured disk. When a bubble is stuck on "downloading…" this
 * walks the same path and reports which step is broken.
 */
class DiagnoseMediaPipeline extends Command
{
    protected $signature = 'media:diagnose {--team= : Team id (defaults to the first team)}';

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
        $disk = config('filesystems.default', 'public');

        $this->info("Team #{$team->id}  ·  disk: {$disk}  ·  driver: ".config("filesystems.disks.{$disk}.driver"));
        $this->newLine();

        $ok = $this->checkAccessToken($team) && $ok;
        $ok = $this->checkDiskWritable($disk) && $ok;
        $ok = $this->checkPublicUrl($disk) && $ok;

        $this->reportStuckMessages($team);

        $this->newLine();
        $this->line($ok ? '<info>Pipeline looks healthy.</info>' : '<error>Pipeline has problems — see above.</error>');

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function checkAccessToken(Team $team): bool
    {
        if (! $team->whatsapp_access_token) {
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

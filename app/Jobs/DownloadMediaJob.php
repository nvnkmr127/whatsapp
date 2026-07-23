<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\Team;
use App\Services\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $messageId;

    public $mediaId;

    public $teamId;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public function __construct($messageId, $mediaId, $teamId)
    {
        $this->messageId = $messageId;
        $this->mediaId = $mediaId;
        $this->teamId = $teamId;
    }

    public function handle(): void
    {
        $message = Message::find($this->messageId);
        $team = Team::find($this->teamId);

        if (! $message || ! $team) {
            return;
        }

        try {
            $mediaService = new MediaService;
            $mediaUrl = $mediaService->downloadAndStore($this->mediaId, $team);

            // downloadAndStore() returns null on every failure path. This used to be
            // written straight into media_url and then logged as SUCCESS: nothing
            // threw, so $tries/$backoff never engaged, and the bubble sat on
            // "downloading…" forever while the log claimed it had worked.
            if (! $mediaUrl) {
                throw new \RuntimeException(
                    "MediaService returned no path (media_id={$this->mediaId}, team={$team->id}) — see the MediaService error above for which step failed"
                );
            }

            $message->update([
                'media_url' => $mediaUrl,
            ]);

            Log::info('DownloadMediaJob SUCCESS', [
                'message_id' => $this->messageId,
                'media_id' => $this->mediaId,
                'path' => $mediaUrl,
                'attempt' => $this->attempts(),
            ]);

            // ── Transcription ──────────────────────────────────────────────
            if ($message->type === 'audio') {
                try {
                    app(\App\Services\VoiceTranscriptionService::class)->transcribe($message);
                } catch (\Exception $e) {
                    Log::warning("Transcription trigger failed for Message #{$message->id}: " . $e->getMessage());
                }
            }

            // Broadcast Update to Client
            \App\Events\MessageReceived::dispatch($message);

        } catch (\Exception $e) {
            Log::error('DownloadMediaJob FAILED', [
                'message_id' => $this->messageId,
                'media_id' => $this->mediaId,
                'team_id' => $this->teamId,
                'attempt' => $this->attempts().'/'.$this->tries,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retries are exhausted — stop the UI claiming the file is still downloading.
     * Recorded in metadata (already broadcast and serialised to the client) rather
     * than a new column, so this needs no migration.
     */
    public function failed(\Throwable $e): void
    {
        $message = Message::find($this->messageId);

        if (! $message) {
            return;
        }

        $metadata = is_array($message->metadata) ? $message->metadata : [];
        $metadata['media_failed'] = true;
        $metadata['media_failed_reason'] = $e->getMessage();
        $message->update(['metadata' => $metadata]);

        Log::error('DownloadMediaJob GAVE UP after all retries', [
            'message_id' => $this->messageId,
            'media_id' => $this->mediaId,
            'team_id' => $this->teamId,
            'error' => $e->getMessage(),
        ]);

        \App\Events\MessageReceived::dispatch($message);
    }
}

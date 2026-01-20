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

        if (!$message || !$team) {
            return;
        }

        try {
            $mediaService = new MediaService();
            $mediaUrl = $mediaService->downloadAndStore($this->mediaId, $team);

            $message->update([
                'media_url' => $mediaUrl,
            ]);

            Log::info("DownloadMediaJob SUCCESS: Message {$this->messageId}");

            // Broadcast Update to Client
            \App\Events\MessageReceived::dispatch($message);

        } catch (\Exception $e) {
            Log::error("DownloadMediaJob FAILED: " . $e->getMessage());
            throw $e;
        }
    }
}

<?php

namespace Tests\Feature\Media;

use App\Jobs\DownloadMediaJob;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadMediaJobTest extends TestCase
{
    use RefreshDatabase;

    private function inboundImage(): Message
    {
        $team = Team::factory()->create(['whatsapp_access_token' => 'valid-token']);
        $conversation = Conversation::factory()->create(['team_id' => $team->id]);

        return Message::factory()->create([
            'team_id' => $team->id,
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'type' => 'image',
            'media_url' => null,
        ]);
    }

    /**
     * The reported bug: a failed download wrote NULL into media_url, logged
     * "SUCCESS", and threw nothing — so $tries never engaged and the bubble sat on
     * "downloading…" forever.
     */
    public function test_a_failed_download_throws_so_the_job_retries(): void
    {
        $configuredDisk = config('filesystems.default');
        $disk = ($configuredDisk === 'local') ? 'public' : $configuredDisk;
        Storage::fake($disk);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'expired'], 400)]);

        $message = $this->inboundImage();

        $this->expectException(\Throwable::class);

        try {
            (new DownloadMediaJob($message->id, '12345', $message->team_id))->handle();
        } finally {
            $this->assertNull($message->fresh()->media_url, 'a failed download must not be recorded as done');
        }
    }

    /** Once retries are exhausted the UI must stop claiming a download is running. */
    public function test_giving_up_marks_the_message_so_the_spinner_stops(): void
    {
        $message = $this->inboundImage();

        (new DownloadMediaJob($message->id, '12345', $message->team_id))
            ->failed(new \RuntimeException('media id expired'));

        $metadata = $message->fresh()->metadata;

        $this->assertTrue($metadata['media_failed'] ?? false, 'bubble keys off metadata.media_failed');
        $this->assertSame('media id expired', $metadata['media_failed_reason'] ?? null);
    }

    /** The happy path still stores the file and links it. */
    public function test_a_successful_download_links_the_media(): void
    {
        $configuredDisk = config('filesystems.default');
        $disk = ($configuredDisk === 'local') ? 'public' : $configuredDisk;
        Storage::fake($disk);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['url' => 'http://media.url/f', 'mime_type' => 'image/jpeg'], 200),
            'http://media.url/f' => Http::response('binary', 200),
        ]);

        $message = $this->inboundImage();

        (new DownloadMediaJob($message->id, '12345', $message->team_id))->handle();

        $fresh = $message->fresh();
        $this->assertNotNull($fresh->media_url);
        Storage::disk($disk)->assertExists($fresh->media_url);
    }
}

<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoiceNoteUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_voice_note_upload_transcodes_to_ogg()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->teams()->attach($team, ['role' => 'admin']);
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user->refresh();
        Sanctum::actingAs($user);

        // Generate a real AAC/m4a (what the phone records). Skip if ffmpeg is absent
        // (this same binary is required in production for the transcode to work).
        $ffmpeg = (new \Symfony\Component\Process\ExecutableFinder)
            ->find('ffmpeg', null, ['/opt/homebrew/bin', '/usr/local/bin', '/usr/bin', '/bin']);
        if (! $ffmpeg) {
            $this->markTestSkipped('ffmpeg not available');
        }
        $fixture = tempnam(sys_get_temp_dir(), 'vn') . '.m4a';
        (new \Symfony\Component\Process\Process([
            $ffmpeg, '-y', '-f', 'lavfi', '-i', 'sine=frequency=440:duration=2',
            '-c:a', 'aac', '-b:a', '128k', $fixture,
        ]))->mustRun();

        $m4a = UploadedFile::fake()->createWithContent('voice.m4a', file_get_contents($fixture));
        @unlink($fixture);

        $res = $this->postJson('/api/v1/mobile/media/upload', [
            'file' => $m4a,
            'is_voice_note' => 'true',
        ], ['User-Agent' => 'TestAgent']);

        $res->assertStatus(200)->assertJsonPath('success', true);

        $body = $res->json();

        $disk = config('filesystems.default') === 'local' ? 'public' : config('filesystems.default');
        $stored = Storage::disk($disk)->path($body['path']);
        $this->assertTrue(file_exists($stored), 'transcoded file should be stored');
        $this->assertEquals('audio/ogg', mime_content_type($stored), 'stored file should be real ogg');
        $this->assertStringEndsWith('.ogg', $body['path'], 'voice note should be transcoded to .ogg');
        $this->assertEquals('audio', $body['type']);

        // --- Step 2: send the message referencing that uploaded url ---
        // Sandbox mode short-circuits the real Meta call but exercises the full
        // controller path (validation, message creation, job dispatch).
        $team->forceFill(['is_sandbox_mode' => true])->save();

        $contact = \App\Models\Contact::factory()->create(['team_id' => $team->id]);
        $conversation = \App\Models\Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'last_message_at' => now(),
        ]);
        // Inbound message so the 24-hour window is open.
        $inbound = \App\Models\Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'type' => 'text',
            'content' => 'hi',
            'status' => 'received',
        ]);
        $inbound->forceFill(['created_at' => now()->subMinutes(5)])->save();

        $sendRes = $this->postJson("/api/v1/mobile/conversations/{$conversation->id}/messages", [
            'type' => 'audio',
            'media_url' => $body['url'],
            'content' => 'Voice message (0:02)',
            'is_voice_note' => true,
        ]);

        $sendRes->assertStatus(200)->assertJsonPath('success', true);

        $msg = \App\Models\Message::where('conversation_id', $conversation->id)
            ->where('direction', 'outbound')->where('type', 'audio')->first();
        $this->assertNotNull($msg, 'outbound audio message should be created');
        $this->assertEquals('sent', $msg->status, 'message should reach sent status in sandbox');
    }
}

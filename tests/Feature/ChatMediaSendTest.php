<?php

namespace Tests\Feature;

use App\Livewire\Chat\MessageWindow;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ChatMediaSendTest extends TestCase
{
    use RefreshDatabase;

    /** Picking a file persists it and exposes the path the send button reacts to. */
    public function test_media_upload_persists_file_and_sets_attachment_data(): void
    {
        $disk = config('filesystems.default');
        Storage::fake($disk);
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $conversation = Conversation::factory()->create(['team_id' => $team->id]);
        $this->actingAs($user);

        Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id])
            ->set('newAttachment', UploadedFile::fake()->image('photo.jpg'))
            ->assertHasNoErrors()
            ->assertSet('uploadError', null)
            ->assertSet('newAttachmentData.name', 'photo.jpg')
            ->tap(fn ($c) => $this->assertNotEmpty($c->get('newAttachmentData')['path']));

        $this->assertNotEmpty(Storage::disk($disk)->files('chat-media'), 'file must be stored');
    }

    /** Sending the picked media builds a correct outbound media message (type/url/caption) and hands it to the send job. */
    public function test_sending_media_creates_outbound_media_message(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $conversation = Conversation::factory()->create(['team_id' => $team->id]);
        $this->actingAs($user);

        Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id])
            ->set('newAttachmentData', ['path' => 'chat-media/abc.jpg', 'mime_type' => 'video/mp4', 'name' => 'clip.mp4'])
            ->set('msgBody', 'look at this')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSet('newAttachmentData', null); // cleared after send

        $msg = Message::where('conversation_id', $conversation->id)
            ->where('direction', 'outbound')->latest('id')->first();

        $this->assertNotNull($msg, 'media message should be created');
        $this->assertSame('video', $msg->type);              // mime → correct WA type
        $this->assertSame('chat-media/abc.jpg', $msg->media_url);
        $this->assertSame('video/mp4', $msg->media_type);
        $this->assertSame('look at this', $msg->caption);    // caption carried through (msgBody, not the removed messageBody)
    }
}

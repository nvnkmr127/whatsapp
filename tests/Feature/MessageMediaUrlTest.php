<?php

namespace Tests\Feature;

use App\Events\MessageReceived;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageMediaUrlTest extends TestCase
{
    use RefreshDatabase;

    private function message(?string $mediaUrl): Message
    {
        $team = Team::factory()->create();
        $conversation = Conversation::factory()->create(['team_id' => $team->id]);

        return Message::factory()->create([
            'conversation_id' => $conversation->id,
            'media_url' => $mediaUrl,
            'type' => $mediaUrl ? 'image' : 'text',
        ]);
    }

    /**
     * Both broadcasts used to build this URL themselves — MessageSent against a
     * hardcoded 'public' disk, so with R2 configured teammates saw broken images
     * for outbound media.
     */
    public function test_both_broadcasts_use_the_same_url_as_the_model(): void
    {
        $message = $this->message('chat-media/photo.jpg');

        $this->assertNotNull($message->full_media_url);
        $this->assertSame($message->full_media_url, (new MessageSent($message))->broadcastWith()['message']['media_url']);
        $this->assertSame($message->full_media_url, (new MessageReceived($message))->broadcastWith()['message']['media_url']);
    }

    /** Mobile uploads store an absolute URL; prefixing a disk onto it breaks the link. */
    public function test_absolute_media_urls_are_passed_through_untouched(): void
    {
        $absolute = 'https://cdn.example.com/mobile/uploads/image/abc.jpg';
        $message = $this->message($absolute);

        $this->assertSame($absolute, $message->full_media_url);
        $this->assertSame($absolute, (new MessageSent($message))->broadcastWith()['message']['media_url']);
        $this->assertSame($absolute, (new MessageReceived($message))->broadcastWith()['message']['media_url']);
    }

    public function test_messages_without_media_broadcast_null(): void
    {
        $message = $this->message(null);

        $this->assertNull((new MessageSent($message))->broadcastWith()['message']['media_url']);
        $this->assertNull((new MessageReceived($message))->broadcastWith()['message']['media_url']);
    }
}

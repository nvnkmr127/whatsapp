<?php

namespace Tests\Feature;

use App\Livewire\Chat\MessageWindow;
use App\Livewire\Chat\SlaDashboard;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\SlaPolicy;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatTranscriptExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_transcript_streams_text_file(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $conversation = Conversation::factory()->create(['team_id' => $team->id]);
        Message::factory()->create([
            'team_id' => $team->id,
            'conversation_id' => $conversation->id,
            'content' => 'Hello support team',
            'direction' => 'inbound',
        ]);

        Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id])
            ->call('downloadTranscript')
            ->assertFileDownloaded("transcript_conv_{$conversation->id}.txt");
    }

    public function test_sla_dashboard_blocks_editing_other_team_policy(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $userA = User::factory()->create(['current_team_id' => $teamA->id]);

        $policyB = SlaPolicy::create([
            'team_id' => $teamB->id,
            'name' => 'Foreign Policy',
            'first_response_minutes' => 60,
            'resolution_minutes' => 120,
        ]);

        $this->actingAs($userA);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(SlaDashboard::class)
            ->call('editPolicy', $policyB->id);
    }
}

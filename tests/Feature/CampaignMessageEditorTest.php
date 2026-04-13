<?php

namespace Tests\Feature;

use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CampaignMessageEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_editor_renders_team_templates(): void
    {
        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'hello_world',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}'],
            ],
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Campaigns\Wizard\MessageEditor::class)
            ->assertSee('hello world');
    }
}


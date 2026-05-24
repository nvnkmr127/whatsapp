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

    public function test_wizard_syncs_properties_from_message_editor(): void
    {
        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $template = WhatsappTemplate::create([
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
            ->test(\App\Livewire\Campaigns\Wizard::class)
            ->dispatch('messageEditorUpdated',
                selectedTemplateId: $template->id,
                templateVars: ['Alice'],
                headerMediaUrl: 'https://example.com/image.jpg',
                dripSteps: []
            )
            ->assertSet('selectedTemplateId', $template->id)
            ->assertSet('templateVars', ['Alice'])
            ->assertSet('headerMediaUrl', 'https://example.com/image.jpg');
    }

    public function test_wizard_drip_step_management(): void
    {
        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'hello_world',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}'],
            ],
        ]);

        $editor = Livewire::actingAs($user)
            ->test(\App\Livewire\Campaigns\Wizard\MessageEditor::class, [
                'campaignType' => 'drip',
                'selectedTemplateId' => $template->id,
                'templateVars' => ['Alice'],
                'dripSteps' => [],
            ]);

        // 1. Initially on main step (0)
        $editor->assertSet('currentDripStep', 0)
            ->assertSet('selectedTemplateId', $template->id)
            ->assertSet('templateVars', ['Alice']);

        // 2. Add drip step
        $editor->call('addDripStep')
            ->assertSet('currentDripStep', 1)
            ->assertSet('selectedTemplateId', '')
            ->assertSet('templateVars', []);

        // 3. Select a template for follow-up step (drip step index 0)
        $editor->set('selectedTemplateId', $template->id)
            ->set('templateVars', ['Bob'])
            ->assertSet('dripSteps.0.template_id', $template->id)
            ->assertSet('dripSteps.0.variables', ['Bob']);

        // 4. Select step 0 (main step) again, main step template and vars should be restored
        $editor->call('selectDripStep', 0)
            ->assertSet('currentDripStep', 0)
            ->assertSet('selectedTemplateId', $template->id)
            ->assertSet('templateVars', ['Alice']);

        // 5. Select step 1 (follow-up step) again, Bob variables should be restored
        $editor->call('selectDripStep', 1)
            ->assertSet('currentDripStep', 1)
            ->assertSet('selectedTemplateId', $template->id)
            ->assertSet('templateVars', ['Bob']);

        // 6. Remove drip step
        $editor->call('removeDripStep', 0)
            ->assertCount('dripSteps', 0)
            ->assertSet('currentDripStep', 0);
    }
}



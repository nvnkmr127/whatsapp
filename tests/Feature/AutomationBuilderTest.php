<?php

namespace Tests\Feature;

use App\Livewire\Automations\AutomationBuilder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AutomationBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUser()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->personalTeam()->id;
        $user->save();

        return $user->fresh();
    }

    public function test_can_load_automation_builder()
    {
        $user = $this->setupUser();

        Livewire::actingAs($user)
            ->test(AutomationBuilder::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.automations.automation-builder');
    }

    public function test_can_add_node_to_automation()
    {
        $user = $this->setupUser();

        Livewire::actingAs($user)
            ->test(AutomationBuilder::class)
            ->call('addNode', 'text')
            ->assertSet('nodes.1.type', 'text')
            ->assertCount('nodes', 2);
    }

    public function test_can_add_edge_and_fail_validation_if_incomplete()
    {
        $user = $this->setupUser();

        Livewire::actingAs($user)
            ->test(AutomationBuilder::class)
            ->call('addNode', 'text')
            ->set('nodeText', '')
            ->call('updateNodeData')
            ->call('runValidation')
            ->assertSet('isActivatable', false);
    }

    public function test_can_save_valid_automation()
    {
        $user = $this->setupUser();

        Livewire::actingAs($user)
            ->test(AutomationBuilder::class)
            ->set('name', 'My New Bot')
            ->call('addNode', 'text')
            ->set('nodeText', 'Hello there')
            ->call('updateNodeData')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('automations', [
            'name' => 'My New Bot',
            'team_id' => $user->currentTeam->id,
        ]);
    }

    public function test_ab_split_validation()
    {
        $user = $this->setupUser();

        Livewire::actingAs($user)
            ->test(AutomationBuilder::class)
            ->call('addNode', 'ab_split')
            ->call('runValidation')
            ->assertSet('isActivatable', false);
    }

    public function test_publish_requires_version_notes()
    {
        $user = $this->setupUser();

        Livewire::actingAs($user)
            ->test(AutomationBuilder::class)
            ->set('name', 'My Publish Bot')
            ->call('addNode', 'text')
            ->set('nodeText', 'Hello')
            ->call('updateNodeData')
            ->call('publish')
            ->assertSet('showPublishModal', true)
            ->set('publishNote', '')
            ->call('confirmPublish')
            ->assertHasErrors(['publishNote']);
    }

    public function test_publish_creates_version_one_on_first_publish()
    {
        $user = $this->setupUser();

        Livewire::actingAs($user)
            ->test(AutomationBuilder::class)
            ->set('name', 'My Publish Bot')
            ->call('addNode', 'text')
            ->set('nodeText', 'Hello')
            ->call('updateNodeData')
            ->set('publishNote', 'First publish notes')
            ->call('confirmPublish')
            ->assertHasNoErrors()
            ->assertSet('version', 1);

        $this->assertDatabaseHas('automations', [
            'name' => 'My Publish Bot',
            'version' => 1,
            'is_active' => true,
        ]);
        
        $automation = \App\Models\Automation::where('name', 'My Publish Bot')->first();
        $this->assertNotNull($automation->last_published_at);
        $this->assertCount(1, $automation->publish_log);
        $this->assertEquals('First publish notes', $automation->publish_log[0]['note']);
        $this->assertNotNull($automation->publish_log[0]['nodes']);
    }

    public function test_can_rollback_to_version()
    {
        $user = $this->setupUser();
        $team = $user->currentTeam;

        // Create an automation with some history
        $nodesV1 = [['id' => 'Start', 'type' => 'trigger'], ['id' => 'n1', 'type' => 'text', 'data' => ['text' => 'v1 content']]];
        $nodesV2 = [['id' => 'Start', 'type' => 'trigger'], ['id' => 'n1', 'type' => 'text', 'data' => ['text' => 'v2 content']]];

        $automation = \App\Models\Automation::create([
            'team_id' => $team->id,
            'name' => 'Rollback Bot',
            'is_active' => true,
            'trigger_type' => 'keyword',
            'version' => 2,
            'last_published_at' => now(),
            'flow_data' => ['nodes' => $nodesV2, 'edges' => []],
            'publish_log' => [
                [
                    'version' => 2,
                    'note' => 'V2 notes',
                    'published_at' => now()->toDateTimeString(),
                    'published_by' => $user->name,
                    'nodes' => $nodesV2,
                    'edges' => [],
                ],
                [
                    'version' => 1,
                    'note' => 'V1 notes',
                    'published_at' => now()->subDay()->toDateTimeString(),
                    'published_by' => $user->name,
                    'nodes' => $nodesV1,
                    'edges' => [],
                ]
            ]
        ]);

        Livewire::actingAs($user)
            ->test(AutomationBuilder::class, ['automationId' => $automation->id])
            ->assertSet('version', 2)
            ->call('viewVersion', 1)
            ->assertSet('selectedLogVersion.version', 1)
            ->call('rollbackToVersion', 1)
            ->assertSet('selectedLogVersion', null)
            ->assertSet('isDirty', true)
            ->assertSet('nodes.1.data.text', 'v1 content');
    }
}

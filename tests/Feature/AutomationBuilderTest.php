<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Automation;
use App\Livewire\Automations\AutomationBuilder;
use Livewire\Livewire;

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
            'team_id' => $user->currentTeam->id
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
}

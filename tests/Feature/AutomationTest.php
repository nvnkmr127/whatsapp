<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_process_keyword_trigger()
    {
        $team = \App\Models\Team::factory()->create();
        $contact = \App\Models\Contact::factory()->create(['team_id' => $team->id]);

        // Mock WhatsAppService
        $this->mock(\App\Services\WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('setTeam')->once();
            $mock->shouldReceive('sendText')->once()->withArgs(function ($phone, $text) {
                return $text === 'Hello World'; // Check automated reply
            });
        });

        // Create Automation
        $automation = \App\Models\Automation::create([
            'team_id' => $team->id,
            'name' => 'Hello Bot',
            'is_active' => true,
            'trigger_type' => 'keyword',
            'trigger_config' => ['keywords' => ['hello']],
            'flow_data' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'trigger'],
                    ['id' => '2', 'type' => 'message', 'data' => ['text' => 'Hello World']]
                ],
                'edges' => [
                    ['source' => '1', 'target' => '2']
                ]
            ]
        ]);

        $service = new \App\Services\AutomationService(app(\App\Services\WhatsAppService::class));

        // Assert Checked Triggers
        $triggered = $service->checkTriggers($contact, 'hello there');
        $this->assertTrue($triggered);

        // Assert Automation Run Created
        $this->assertDatabaseHas('automation_runs', [
            'contact_id' => $contact->id,
            'automation_id' => $automation->id,
            'status' => 'completed' // Should finish immediately as it's 1 message
        ]);
    }

    public function test_can_process_branching()
    {
        $team = \App\Models\Team::factory()->create();
        $contact = \App\Models\Contact::factory()->create(['team_id' => $team->id]);

        // Mock WhatsAppService
        $this->mock(\App\Services\WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('setTeam');
            $mock->shouldReceive('sendText');
        });

        // Create Branching Automation
        // Q: 1 (Start) -> 2 (Question) -> Edge A (Yes) -> 3 (Message A)
        //                             -> Edge B (No) -> 4 (Message B)
        $automation = \App\Models\Automation::create([
            'team_id' => $team->id,
            'name' => 'Support Bot',
            'is_active' => true,
            'trigger_type' => 'keyword',
            'trigger_config' => ['keywords' => ['support']],
            'flow_data' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'trigger'],
                    ['id' => '2', 'type' => 'question', 'data' => ['text' => 'Need help?']],
                    ['id' => '3', 'type' => 'message', 'data' => ['text' => 'Glad to help']],
                    ['id' => '4', 'type' => 'message', 'data' => ['text' => 'Okay, bye']]
                ],
                'edges' => [
                    ['source' => '1', 'target' => '2'],
                    ['source' => '2', 'target' => '3', 'condition' => 'yes'],
                    ['source' => '2', 'target' => '4', 'condition' => 'no']
                ]
            ]
        ]);

        $service = new \App\Services\AutomationService(app(\App\Services\WhatsAppService::class));

        // 1. Initial trigger
        $service->checkTriggers($contact, 'support');

        $run = \App\Models\AutomationRun::where('contact_id', $contact->id)->first();
        $this->assertEquals('waiting_input', $run->status);
        $this->assertEquals('2', $run->state_data['current_node_id']);

        // 2. Reply "Yes"
        $service->handleReply($contact, 'yes');

        $run->refresh();
        $this->assertEquals('completed', $run->status);
        $this->assertEquals('3', $run->state_data['current_node_id']);
    }

    public function test_can_save_automation_builder()
    {
        $user = \App\Models\User::factory()->withPersonalTeam()->create();

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Automations\AutomationBuilder::class)
            ->set('name', 'Test Bot')
            ->set('triggerKeyword', 'test')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('automations', [
            'team_id' => $user->currentTeam->id,
            'name' => 'Test Bot'
        ]);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
            $mock->shouldReceive('setTeam');
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
                    ['id' => '2', 'type' => 'message', 'data' => ['text' => 'Hello World']],
                ],
                'edges' => [
                    ['source' => '1', 'target' => '2'],
                ],
            ],
        ]);

        $service = app(\App\Services\AutomationService::class);

        // Assert Checked Triggers
        $triggered = $service->checkTriggers($contact, 'hello there');
        $this->assertTrue($triggered);

        // Assert Automation Run Created
        $this->assertDatabaseHas('automation_runs', [
            'contact_id' => $contact->id,
            'automation_id' => $automation->id,
            'status' => 'completed', // Should finish immediately as it's 1 message
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
                    ['id' => '2', 'type' => 'user_input', 'data' => ['question' => 'Need help?', 'variable' => 'help_needed']],
                    ['id' => '3', 'type' => 'message', 'data' => ['text' => 'Glad to help']],
                    ['id' => '4', 'type' => 'message', 'data' => ['text' => 'Okay, bye']],
                ],
                'edges' => [
                    ['source' => '1', 'target' => '2'],
                    ['source' => '2', 'target' => '3', 'condition' => 'yes'],
                    ['source' => '2', 'target' => '4', 'condition' => 'no'],
                ],
            ],
        ]);

        $service = app(\App\Services\AutomationService::class);

        // 1. Initial trigger
        $triggered = $service->checkTriggers($contact, 'support');
        $this->assertTrue($triggered);

        $run = \App\Models\AutomationRun::where('contact_id', $contact->id)->first();
        $this->assertNotNull($run, 'AutomationRun should be created');
        $this->assertEquals('waiting_input', $run->status);
        $this->assertEquals('2', $run->state_data['current_node_id']);

        // 2. Reply "Yes"
        $service->handleReply($contact, 'yes');

        $run->refresh();
        $this->assertEquals('completed', $run->status);
        $this->assertEquals('3', $run->state_data['current_node_id']);
    }

    public function test_interactive_button_node_parks_run_in_waiting_input()
    {
        $team = \App\Models\Team::factory()->create();
        $contact = \App\Models\Contact::factory()->create(['team_id' => $team->id]);

        $this->mock(\App\Services\WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('setTeam');
            $mock->shouldReceive('sendInteractiveButtons')->once();
        });

        \App\Models\Automation::create([
            'team_id' => $team->id,
            'name' => 'Button Bot',
            'is_active' => true,
            'trigger_type' => 'keyword',
            'trigger_config' => ['keywords' => ['menu']],
            'flow_data' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'trigger'],
                    ['id' => '2', 'type' => 'interactive_button', 'data' => [
                        'text' => 'Pick one',
                        'buttons' => [['id' => 'opt_a', 'title' => 'A']],
                    ]],
                    ['id' => '3', 'type' => 'message', 'data' => ['text' => 'done']],
                ],
                'edges' => [
                    ['source' => '1', 'target' => '2'],
                    ['source' => '2', 'target' => '3', 'condition' => 'opt_a'],
                ],
            ],
        ]);

        app(\App\Services\AutomationService::class)->checkTriggers($contact, 'menu');

        $run = \App\Models\AutomationRun::where('contact_id', $contact->id)->first();
        $this->assertNotNull($run);
        // Regression: interactive button/list nodes must park the run so the reply is matched.
        $this->assertEquals('waiting_input', $run->status);
        $this->assertEquals('2', $run->state_data['current_node_id']);
    }

    public function test_delay_node_pauses_run_using_editor_schema()
    {
        $team = \App\Models\Team::factory()->create();
        $contact = \App\Models\Contact::factory()->create(['team_id' => $team->id]);

        $this->mock(\App\Services\WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('setTeam');
            $mock->shouldReceive('sendText')->never();
        });

        \App\Models\Automation::create([
            'team_id' => $team->id,
            'name' => 'Delay Bot',
            'is_active' => true,
            'trigger_type' => 'keyword',
            'trigger_config' => ['keywords' => ['wait']],
            'flow_data' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'trigger'],
                    // Editor persists { value, time_unit } — the engine must honour it.
                    ['id' => '2', 'type' => 'delay', 'data' => ['value' => 2, 'time_unit' => 'hours']],
                    ['id' => '3', 'type' => 'message', 'data' => ['text' => 'later']],
                ],
                'edges' => [
                    ['source' => '1', 'target' => '2'],
                    ['source' => '2', 'target' => '3'],
                ],
            ],
        ]);

        app(\App\Services\AutomationService::class)->checkTriggers($contact, 'wait');

        $run = \App\Models\AutomationRun::where('contact_id', $contact->id)->first();
        $this->assertNotNull($run);
        $this->assertEquals('paused', $run->status);
        $this->assertNotNull($run->resume_at);
        $this->assertEquals('2', $run->state_data['current_node_id']);
    }

    public function test_update_contact_ignores_empty_value()
    {
        $team = \App\Models\Team::factory()->create();
        $contact = \App\Models\Contact::factory()->create(['team_id' => $team->id, 'name' => 'Original']);

        $this->mock(\App\Services\WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('setTeam');
            $mock->shouldReceive('sendText');
        });

        \App\Models\Automation::create([
            'team_id' => $team->id,
            'name' => 'Update Bot',
            'is_active' => true,
            'trigger_type' => 'keyword',
            'trigger_config' => ['keywords' => ['go']],
            'flow_data' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'trigger'],
                    // Misconfigured: empty value must NOT blank the contact's name.
                    ['id' => '2', 'type' => 'update_contact', 'data' => ['field' => 'name', 'value' => '']],
                ],
                'edges' => [['source' => '1', 'target' => '2']],
            ],
        ]);

        app(\App\Services\AutomationService::class)->checkTriggers($contact, 'go');

        $contact->refresh();
        $this->assertEquals('Original', $contact->name);
    }

    public function test_can_save_automation_builder()
    {
        $user = \App\Models\User::factory()->withPersonalTeam()->create();

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Automations\AutomationBuilder::class)
            ->set('name', 'Test Bot')
            ->set('triggerKeywordsString', 'test')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('automations', [
            'team_id' => $user->currentTeam->id,
            'name' => 'Test Bot',
        ]);
    }
}

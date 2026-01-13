<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Flow;
use App\Models\FlowSession;
use App\Models\Team;
use App\Services\BotFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function createTestFlow($team)
    {
        // Ensure team has credentials
        $team->update([
            'whatsapp_access_token' => 'fake_token',
            'whatsapp_phone_number_id' => '123456789'
        ]);

        // Simple Flow: Start -> Ask Name -> End
        return Flow::create([
            'team_id' => $team->id,
            'name' => 'Test Flow',
            'trigger_keyword' => 'HELLO',
            'is_active' => true,
            'nodes' => [
                [
                    'id' => 'step_1',
                    'type' => 'message', // Greeting
                    'data' => ['content' => 'Hi there!']
                ],
                [
                    'id' => 'step_2',
                    'type' => 'question', // Ask Name
                    'data' => ['content' => 'What is your name?']
                ],
                [
                    'id' => 'step_3',
                    'type' => 'message', // Thanks
                    'data' => ['content' => 'Thanks!']
                ]
            ],
            'edges' => [
                ['source' => 'step_1', 'target' => 'step_2'],
                ['source' => 'step_2', 'target' => 'step_3']
            ]
        ]);
    }

    public function test_matches_keyword_and_starts_flow()
    {
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);
        $flow = $this->createTestFlow($team);

        $service = new BotFlowService();
        $result = $service->handleKeyword($contact, 'hello');

        $this->assertTrue($result);
        $this->assertDatabaseHas('flow_sessions', [
            'contact_id' => $contact->id,
            'flow_id' => $flow->id,
            'status' => 'active'
        ]);

        // Should have advanced to step_2 (Question) because step_1 is auto-advance 'message'
        $session = FlowSession::where('contact_id', $contact->id)->first();
        $this->assertEquals('step_2', $session->current_step_id);
    }

    public function test_processes_input_for_question_node()
    {
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);
        $flow = $this->createTestFlow($team);

        // Manually start session at step_2 (Question)
        $session = FlowSession::create([
            'flow_id' => $flow->id,
            'contact_id' => $contact->id,
            'current_step_id' => 'step_2',
            'state' => [],
            'status' => 'active'
        ]);

        $service = new BotFlowService();
        $result = $service->processInput($contact, 'My name is John');

        $this->assertTrue($result);

        $session->refresh();

        // Should have stored state
        $this->assertEquals('My name is John', $session->state['step_2']);

        // Should have moved to step_3 (Message) -> And if step_3 is message, it might auto-complete if no next node
        // In my flow, step_3 has NO outgoing edge.
        // So step_3 executes -> determinesNextNode -> null -> status=completed.
        // But wait, executeStep for step_3 (message) recurses.
        // It tries to find next. If null, completes.

        // So final state should be completed.
        // Or if recursion happened instantly, step_3 was executed.

        $this->assertEquals('completed', $session->status);
    }
}

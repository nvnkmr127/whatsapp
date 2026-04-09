<?php

namespace Tests\Feature;

use App\Jobs\ExecuteAutomationNodeJob;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Contact;
use App\Models\Team;
use App\Services\AutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationRecursionTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_steps_dispatch_async()
    {
        Queue::fake();

        // 1. Setup
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        // Simple 2-node flow
        $flowData = [
            'nodes' => [
                ['id' => 'node_1', 'type' => 'trigger'],
                ['id' => 'node_2', 'type' => 'text', 'data' => ['text' => 'Hi']],
            ],
            'edges' => [
                ['source' => 'node_1', 'target' => 'node_2'],
            ],
        ];

        $automation = Automation::create([
            'team_id' => $team->id,
            'name' => 'Test Automation',
            'trigger_type' => 'keyword',
            'is_active' => true,
            'flow_data' => $flowData,
        ]);

        $run = AutomationRun::create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'status' => 'active',
            'state_data' => ['current_node_id' => 'node_1'],
        ]);

        // 2. Execute Logic
        $service = app(AutomationService::class);

        // Simulate completing node_1 and moving to node_2
        $service->moveToNextNode($run);

        // 3. Assert Async Dispatch
        // This confirms we are NOT calling executeNodeSync recursively
        Queue::assertPushed(ExecuteAutomationNodeJob::class, function ($job) use ($run) {
            return $job->runId === $run->id && $job->nodeId === 'node_2';
        });
    }
}

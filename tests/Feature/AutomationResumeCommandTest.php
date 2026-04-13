<?php

namespace Tests\Feature;

use App\Jobs\ExecuteAutomationNodeJob;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Contact;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationResumeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resumes_due_paused_runs_and_dispatches_jobs(): void
    {
        Queue::fake();

        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        $automation = Automation::create([
            'team_id' => $team->id,
            'name' => 'Resume Test',
            'is_active' => true,
            'trigger_type' => 'manual',
            'trigger_config' => [],
            'flow_data' => ['nodes' => [], 'edges' => []],
        ]);

        $run = AutomationRun::create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'status' => 'paused',
            'resume_at' => now()->subMinute(),
            'state_data' => ['current_node_id' => 'node-1'],
        ]);

        $this->artisan('automation:resume')->assertExitCode(0);

        $run->refresh();
        $this->assertEquals('active', $run->status);
        $this->assertNull($run->resume_at);

        Queue::assertPushed(ExecuteAutomationNodeJob::class, function (ExecuteAutomationNodeJob $job) use ($run) {
            return $job->runId === $run->id && $job->nodeId === 'node-1';
        });
    }

    public function test_it_marks_runs_failed_when_state_has_no_current_node_id(): void
    {
        Queue::fake();

        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        $automation = Automation::create([
            'team_id' => $team->id,
            'name' => 'Resume Missing Node Test',
            'is_active' => true,
            'trigger_type' => 'manual',
            'trigger_config' => [],
            'flow_data' => ['nodes' => [], 'edges' => []],
        ]);

        $run = AutomationRun::create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'status' => 'paused',
            'resume_at' => now()->subMinute(),
            'state_data' => [],
        ]);

        $this->artisan('automation:resume')->assertExitCode(0);

        $run->refresh();
        $this->assertEquals('failed', $run->status);
        $this->assertNull($run->resume_at);

        Queue::assertNotPushed(ExecuteAutomationNodeJob::class);
    }
}


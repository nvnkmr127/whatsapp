<?php

namespace Tests\Feature;

use App\Livewire\Settings\ChatRouting;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatRoutingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Team, 2: User} [owner, team, agent] */
    private function teamWithAgent(): array
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $agent = User::factory()->create();
        $team->users()->attach($agent, ['role' => 'agent', 'receives_tickets' => true]);

        return [$owner, $team, $agent];
    }

    private function setRule(Team $team, array $conditions, User $agent): void
    {
        $team->forceFill(['chat_assignment_config' => [
            'rules' => [[
                'priority' => 1,
                'conditions' => $conditions,
                'assign_to' => ['type' => 'user', 'id' => $agent->id],
            ]],
        ]])->save();
    }

    public function test_source_condition_matches_opt_in_source(): void
    {
        [, $team, $agent] = $this->teamWithAgent();
        $this->setRule($team, [['type' => 'source', 'value' => 'web']], $agent);

        $contact = Contact::factory()->create(['team_id' => $team->id, 'opt_in_source' => 'web']);

        $result = app(AssignmentService::class)->simulate($contact);

        $this->assertSame('success', $result['status']);
        $this->assertSame($agent->name, $result['agent_name']);
    }

    public function test_phone_country_condition_matches_phone_number(): void
    {
        [, $team, $agent] = $this->teamWithAgent();
        $this->setRule($team, [['type' => 'phone_country', 'value' => '+91']], $agent);

        $match = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '+919812345678']);
        $noMatch = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '+14155550000']);

        $engine = app(AssignmentService::class);
        // +91 contact hits the rule; +1 contact falls through to round-robin (still the only agent).
        $this->assertSame('Custom Rule Match', $engine->simulate($match)['reason']);
        $this->assertSame('Round Robin (Load Balanced)', $engine->simulate($noMatch)['reason']);
    }

    public function test_simulator_matches_tag_rule(): void
    {
        [$owner, $team, $agent] = $this->teamWithAgent();
        $this->setRule($team, [['type' => 'tag', 'value' => 'vip']], $agent);

        Livewire::actingAs($owner)
            ->test(ChatRouting::class)
            ->set('simulationTags', 'vip')
            ->call('runSimulation')
            ->assertSet('simulationResult.status', 'success')
            ->assertSet('simulationResult.agent_name', $agent->name);
    }

    public function test_save_rejects_user_target_without_id(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($owner)
            ->test(ChatRouting::class)
            ->set('customRules', [[
                'priority' => 1,
                'conditions' => [['type' => 'tag', 'value' => 'vip']],
                'assign_to' => ['type' => 'user', 'id' => ''],
            ]])
            ->call('saveAssignmentConfig')
            ->assertHasErrors('customRules.0.assign_to.id');
    }
}

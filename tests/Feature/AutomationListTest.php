<?php

namespace Tests\Feature;

use App\Livewire\Automations\AutomationList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AutomationListTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUser()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->personalTeam()->id;
        $user->save();

        return $user->fresh();
    }

    public function test_can_render_automation_list(): void
    {
        $user = $this->setupUser();

        Livewire::actingAs($user)
            ->test(AutomationList::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.automations.automation-list');
    }
}


<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class BasicDetailsPopupTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_it_saves_valid_details(): void
    {
        $user = User::factory()->create([
            'phone' => null,
        ]);

        $team = Team::factory()->create([
            'user_id' => $user->id,
            'name' => "John's Team", // Example default
            'personal_team' => true,
        ]);
        $user->forceFill(['current_team_id' => $team->id])->save();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Onboarding\BasicDetailsPopup::class)
            ->set('phone', '+15551234567')
            ->set('company_name', 'Acme Corp')
            ->call('save')
            ->assertRedirect(route('teams.whatsapp_config'));

        $this->assertEquals('+15551234567', $user->fresh()->phone);
        $this->assertEquals('Acme Corp', $team->fresh()->name);
    }
}

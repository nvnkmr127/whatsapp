<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Livewire\Templates\TemplatePicker;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class PickerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->currentTeam()->associate($this->team);
        $this->user->save();
        $this->actingAs($this->user);
    }

    public function test_it_filters_unsafe_templates_by_default()
    {
        // Safe Template
        $safe = WhatsappTemplate::create([
            'team_id' => $this->team->id,
            'name' => 'safe_tpl',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'is_paused' => false,
            'readiness_score' => 100,
            'components' => []
        ]);

        // Unsafe Templates
        $paused = WhatsappTemplate::create([
            'team_id' => $this->team->id,
            'name' => 'paused_tpl',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'is_paused' => true,
            'readiness_score' => 100,
            'components' => []
        ]);

        $pending = WhatsappTemplate::create([
            'team_id' => $this->team->id,
            'name' => 'pending_tpl',
            'language' => 'en_US',
            'status' => 'PENDING',
            'is_paused' => false,
            'readiness_score' => 80,
            'components' => []
        ]);

        Livewire::test(TemplatePicker::class)
            ->assertSee('safe_tpl')
            ->assertDontSee('paused_tpl')
            ->assertDontSee('pending_tpl')
            ->set('showInactive', true)
            ->assertSee('paused_tpl')
            ->assertSee('pending_tpl');
    }

    public function test_category_filtering()
    {
        WhatsappTemplate::create([
            'team_id' => $this->team->id,
            'name' => 'marketing_promo',
            'category' => 'MARKETING',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'is_paused' => false,
            'readiness_score' => 100,
            'components' => []
        ]);

        WhatsappTemplate::create([
            'team_id' => $this->team->id,
            'name' => 'utility_update',
            'category' => 'UTILITY',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'is_paused' => false,
            'readiness_score' => 100,
            'components' => []
        ]);

        Livewire::test(TemplatePicker::class, ['allowedCategories' => ['MARKETING']])
            ->assertSee('marketing_promo')
            ->assertDontSee('utility_update');
    }

    public function test_warning_logic()
    {
        $risky = WhatsappTemplate::create([
            'team_id' => $this->team->id,
            'name' => 'risky_tpl',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'readiness_score' => 60,
            'components' => []
        ]);

        $caution = WhatsappTemplate::create([
            'team_id' => $this->team->id,
            'name' => 'caution_tpl',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'readiness_score' => 80,
            'components' => []
        ]);

        Livewire::test(TemplatePicker::class)
            ->set('selectedTemplateId', $risky->id)
            ->assertSet('selectionWarning', "High Risk: This template has a low quality score (60). Delivery may fail.")
            ->set('selectedTemplateId', $caution->id)
            ->assertSet('selectionWarning', "Caution: This template has some issues (80/100).");
    }
}

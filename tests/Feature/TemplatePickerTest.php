<?php

namespace Tests\Feature;

use App\Livewire\Chat\TemplatePicker;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TemplatePickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_open_template_picker_and_select_template(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'whatsapp_template_id' => '12345',
            'name' => 'NEW_LEAD_RECEIVED_V1',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => json_encode([
                [
                    'type' => 'BODY',
                    'text' => 'Hello {{1}}, your lead for {{2}} is received.',
                ],
            ]),
        ]);

        Livewire::test(TemplatePicker::class)
            ->call('openTemplateList')
            ->assertSet('showTemplateListModal', true)
            ->call('selectTemplate', $template->id)
            ->assertSet('showTemplateListModal', false)
            ->assertSet('showTemplatePreviewModal', true)
            ->assertSet('templateVariables', ['1' => '', '2' => ''])
            ->set('templateVariables.1', 'John')
            ->set('templateVariables.2', 'Photography')
            ->call('sendTemplate')
            ->assertDispatched('templateSelected', [
                'template_name' => 'NEW_LEAD_RECEIVED_V1',
                'language' => 'en_US',
                'variables' => ['1' => 'John', '2' => 'Photography'],
            ]);
    }
}

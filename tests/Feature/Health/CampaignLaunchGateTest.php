<?php

namespace Tests\Feature\Health;

use App\Jobs\PrepareCampaignJob;
use App\Livewire\Campaigns\Wizard;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppHealthMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The wizard used to flash "Campaign Launched Successfully!" regardless of
 * health, leaving the campaign stuck in 'scheduled' while campaign:process
 * retried it every minute and only ever logged the failure.
 */
class CampaignLaunchGateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private WhatsappTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();

        // The wizard recalculates audienceCount from real opted-in contacts,
        // so seed them rather than setting the number directly.
        Contact::factory()->count(50)->create([
            'team_id' => $this->user->currentTeam->id,
            'opt_in_status' => 'opted_in',
        ]);

        $this->template = WhatsappTemplate::create([
            'team_id' => $this->user->currentTeam->id,
            'name' => 'hello_world',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => 'Hello']],
        ]);
    }

    private function mockBlocking(array $issues): void
    {
        $this->mock(WhatsAppHealthMonitor::class)
            ->shouldReceive('getBlockingIssues')
            ->andReturn($issues);
    }

    private function wizard()
    {
        return Livewire::actingAs($this->user)
            ->test(Wizard::class)
            ->set('name', 'Test Campaign')
            ->set('selectedTemplateId', $this->template->id)
            ->set('audienceType', 'all');
    }

    public function test_it_refuses_an_immediate_send_while_blocked(): void
    {
        Queue::fake();
        $this->mockBlocking(['Access token expired']);

        $this->wizard()
            ->set('scheduleMode', 'now')
            ->call('launch')
            ->assertHasErrors('launch');

        // The important part: nothing was created and nothing was queued, so the
        // user is not told it worked.
        Queue::assertNotPushed(PrepareCampaignJob::class);
        $this->assertSame(0, Campaign::count());
    }

    public function test_the_refusal_names_the_actual_issue(): void
    {
        Queue::fake();
        $this->mockBlocking(['Access token expired']);

        $component = $this->wizard()
            ->set('scheduleMode', 'now')
            ->call('launch');

        $this->assertStringContainsString(
            'Access token expired',
            $component->errors()->first('launch')
        );
    }

    public function test_a_healthy_account_launches_normally(): void
    {
        Queue::fake();
        $this->mockBlocking([]);

        $this->wizard()
            ->set('scheduleMode', 'now')
            ->call('launch')
            ->assertHasNoErrors();

        Queue::assertPushed(PrepareCampaignJob::class);
        $this->assertSame(1, Campaign::count());
    }

    public function test_scheduling_for_later_is_not_blocked_by_todays_health(): void
    {
        Queue::fake();
        $this->mockBlocking(['Access token expired']);

        // Health is re-checked at send time; today's expired token says nothing
        // about next week, so this must not be refused now.
        $this->wizard()
            ->set('scheduleMode', 'later')
            ->set('scheduled_at', now()->addDays(3)->format('Y-m-d\TH:i'))
            ->call('launch')
            ->assertHasNoErrors();

        $this->assertSame(1, Campaign::count());
    }
}

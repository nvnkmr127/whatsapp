<?php

namespace Tests\Feature\Health;

use App\Models\Contact;
use App\Models\Message;
use App\Models\QualityRatingHistory;
use App\Models\Team;
use App\Services\Health\TemplateAttributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TemplateAttributionTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Contact $contact;

    private TemplateAttributionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->contact = Contact::factory()->create(['team_id' => $this->team->id]);
        $this->service = new TemplateAttributionService;
    }

    /** Timestamps are guarded, so backdate after creation. */
    private function backdate($model, Carbon $at)
    {
        $model->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();

        return $model;
    }

    private function templateSend(string $name, Carbon $at, string $direction = 'outbound'): Message
    {
        return $this->backdate(Message::create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'direction' => $direction,
            'type' => 'template',
            'status' => 'sent',
            'metadata' => ['template_name' => $name],
        ]), $at);
    }

    private function ratingChange(string $from, string $to, Carbon $at): QualityRatingHistory
    {
        return $this->backdate(QualityRatingHistory::create([
            'team_id' => $this->team->id,
            'previous_rating' => $from,
            'new_rating' => $to,
            'severity' => 'info',
        ]), $at);
    }

    public function test_it_ranks_templates_by_volume_in_the_window(): void
    {
        $drop = now();

        foreach (range(1, 5) as $i) {
            $this->templateSend('promo_blast', $drop->copy()->subHours(10));
        }
        foreach (range(1, 2) as $i) {
            $this->templateSend('order_update', $drop->copy()->subHours(20));
        }

        $result = $this->service->templatesBefore($this->team->id, $drop);

        $this->assertCount(2, $result);
        $this->assertSame('promo_blast', $result[0]['template']);
        $this->assertSame(5, $result[0]['sent']);
        $this->assertSame(71.4, $result[0]['share']);
        $this->assertSame('order_update', $result[1]['template']);
    }

    public function test_sends_outside_the_window_are_excluded(): void
    {
        $drop = now();

        $this->templateSend('inside', $drop->copy()->subHours(10));
        $this->templateSend('too_old', $drop->copy()->subHours(100));
        $this->templateSend('after_the_drop', $drop->copy()->addHour());

        $result = $this->service->templatesBefore($this->team->id, $drop);

        $this->assertCount(1, $result);
        $this->assertSame('inside', $result[0]['template']);
    }

    public function test_inbound_and_non_template_messages_are_ignored(): void
    {
        $drop = now();

        $this->templateSend('real_template', $drop->copy()->subHours(5));
        $this->templateSend('inbound_template', $drop->copy()->subHours(5), 'inbound');

        $this->backdate(Message::create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'direction' => 'outbound',
            'type' => 'text',
            'status' => 'sent',
            'content' => 'plain text',
        ]), $drop->copy()->subHours(5));

        $result = $this->service->templatesBefore($this->team->id, $drop);

        $this->assertCount(1, $result);
        $this->assertSame('real_template', $result[0]['template']);
    }

    public function test_another_teams_sends_are_never_attributed(): void
    {
        $drop = now();
        $otherTeam = Team::factory()->create();
        $otherContact = Contact::factory()->create(['team_id' => $otherTeam->id]);

        $this->backdate(Message::create([
            'team_id' => $otherTeam->id,
            'contact_id' => $otherContact->id,
            'direction' => 'outbound',
            'type' => 'template',
            'status' => 'sent',
            'metadata' => ['template_name' => 'other_teams_template'],
        ]), $drop->copy()->subHours(5));

        $this->assertSame([], $this->service->templatesBefore($this->team->id, $drop));
    }

    public function test_it_returns_empty_when_nothing_was_sending(): void
    {
        $this->assertSame([], $this->service->templatesBefore($this->team->id, now()));
    }

    public function test_latest_drop_finds_only_downgrades(): void
    {
        $this->ratingChange('RED', 'GREEN', now()->subDay());

        $this->assertNull($this->service->latestDrop($this->team->id, 30));

        $this->ratingChange('GREEN', 'YELLOW', now()->subHours(2));

        $drop = $this->service->latestDrop($this->team->id, 30);

        $this->assertNotNull($drop);
        $this->assertSame('YELLOW', strtoupper($drop->new_rating));
    }

    public function test_latest_drop_respects_the_range(): void
    {
        $this->ratingChange('GREEN', 'RED', now()->subDays(40));

        $this->assertNull($this->service->latestDrop($this->team->id, 30));
        $this->assertNotNull($this->service->latestDrop($this->team->id, 90));
    }
}

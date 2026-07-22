<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TenantScope used to return early whenever the app was running in console.
 * PHPUnit runs in console, so tenant isolation was inactive in every single
 * test and a cross-tenant leak could never have been caught. Every assertion
 * in this file would have failed to protect anything before that was removed.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Team $mine;

    private Team $theirs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mine = Team::factory()->create();
        $this->theirs = Team::factory()->create();
        $this->user = User::factory()->create(['current_team_id' => $this->mine->id]);
    }

    public function test_contacts_are_isolated(): void
    {
        Contact::factory()->create(['team_id' => $this->mine->id, 'name' => 'Mine']);
        Contact::factory()->create(['team_id' => $this->theirs->id, 'name' => 'Theirs']);

        $this->actingAs($this->user);

        $names = Contact::pluck('name');

        $this->assertContains('Mine', $names);
        $this->assertNotContains('Theirs', $names);
    }

    public function test_campaigns_are_isolated(): void
    {
        Campaign::create(['team_id' => $this->mine->id, 'name' => 'Mine', 'status' => 'draft']);
        Campaign::create(['team_id' => $this->theirs->id, 'name' => 'Theirs', 'status' => 'draft']);

        $this->actingAs($this->user);

        $this->assertSame(['Mine'], Campaign::pluck('name')->all());
    }

    public function test_another_teams_record_cannot_be_fetched_by_id(): void
    {
        $theirContact = Contact::factory()->create(['team_id' => $this->theirs->id]);

        $this->actingAs($this->user);

        // Guessing an id must not be enough to read another tenant's row.
        $this->assertNull(Contact::find($theirContact->id));
    }

    public function test_messages_are_isolated(): void
    {
        $mineConvo = Conversation::factory()->create(['team_id' => $this->mine->id]);
        $theirConvo = Conversation::factory()->create(['team_id' => $this->theirs->id]);

        Message::factory()->create(['conversation_id' => $mineConvo->id, 'content' => 'Mine']);
        Message::factory()->create(['conversation_id' => $theirConvo->id, 'content' => 'Theirs']);

        $this->actingAs($this->user);

        $contents = Message::pluck('content');

        $this->assertContains('Mine', $contents);
        $this->assertNotContains('Theirs', $contents);
    }

    public function test_switching_teams_switches_visibility(): void
    {
        Contact::factory()->create(['team_id' => $this->mine->id, 'name' => 'Mine']);
        Contact::factory()->create(['team_id' => $this->theirs->id, 'name' => 'Theirs']);

        $this->actingAs($this->user);
        $this->assertSame(['Mine'], Contact::pluck('name')->all());

        $this->user->forceFill(['current_team_id' => $this->theirs->id])->save();
        $this->actingAs($this->user->fresh());

        $this->assertSame(['Theirs'], Contact::pluck('name')->all());
    }

    public function test_console_contexts_remain_unscoped(): void
    {
        Contact::factory()->create(['team_id' => $this->mine->id, 'name' => 'Mine']);
        Contact::factory()->create(['team_id' => $this->theirs->id, 'name' => 'Theirs']);

        // No authenticated user: scheduled commands and queue workers legitimately
        // span tenants (whatsapp:calculate-health-scores iterates every team), and
        // must keep doing so.
        $this->assertCount(2, Contact::all());
    }
}

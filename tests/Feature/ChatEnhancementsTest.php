<?php

namespace Tests\Feature;

use App\Livewire\Chat\ConversationList;
use App\Models\Conversation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_filters_resets_per_page_to_25(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $component = Livewire::test(ConversationList::class);

        // Expand per page
        $component->call('loadMore');
        $this->assertSame(50, $component->get('perPage'));

        // Changing read status filter resets perPage
        $component->set('filterReadStatus', 'unread');
        $this->assertSame(25, $component->get('perPage'));

        // Expand again
        $component->call('loadMore');
        $this->assertSame(50, $component->get('perPage'));

        // Changing opt-in status filter resets perPage
        $component->set('filterOptIn', 'yes');
        $this->assertSame(25, $component->get('perPage'));

        // Expand again
        $component->call('loadMore');
        $this->assertSame(50, $component->get('perPage'));

        // Changing blocked filter resets perPage
        $component->set('filterBlocked', 'yes');
        $this->assertSame(25, $component->get('perPage'));
    }
}

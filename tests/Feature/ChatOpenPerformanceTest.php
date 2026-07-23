<?php

namespace Tests\Feature;

use App\Livewire\Chat\MessageWindow;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ChatOpenPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function seedConversation(int $messages = 120): Conversation
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $conversation = Conversation::factory()->create(['team_id' => $team->id]);
        Message::factory()->count($messages)->create(['conversation_id' => $conversation->id]);

        return $conversation;
    }

    /** Opening a chat embeds the first page, so the browser needs no follow-up fetch. */
    public function test_opening_a_chat_embeds_the_first_page_of_messages(): void
    {
        $conversation = $this->seedConversation();

        $view = Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id])
            ->assertHasNoErrors()
            ->html();

        $newest = Message::where('conversation_id', $conversation->id)->latest('id')->first();

        $this->assertStringContainsString((string) $newest->content, $view, 'first page must ship with the initial render');
    }

    /**
     * The inbox re-renders on every inbound message, so its query count must not
     * scale with the number of rows. `hasActiveCall` used to fire one exists()
     * per conversation.
     */
    public function test_inbox_query_count_does_not_scale_with_conversations(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        Conversation::factory()->count(15)->create(['team_id' => $team->id]);

        DB::enableQueryLog();
        Livewire::test(\App\Livewire\Chat\ConversationList::class)->assertHasNoErrors();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        $callQueries = array_filter($queries, fn ($q) => str_contains($q, 'whatsapp_calls'));

        $this->assertLessThanOrEqual(1, count($callQueries), 'active-call lookup must be a single joined query, got '.count($callQueries));
    }

    /** Template lists live inside a closed modal — don't load them to open a chat. */
    public function test_opening_a_chat_does_not_load_templates(): void
    {
        $conversation = $this->seedConversation(5);

        DB::enableQueryLog();
        Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id])->assertHasNoErrors();
        Livewire::test(\App\Livewire\Chat\TemplatePicker::class, ['conversationId' => $conversation->id])->assertHasNoErrors();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        $templateQueries = array_filter($queries, fn ($q) => str_contains($q, 'whatsapp_templates'));

        $this->assertSame([], array_values($templateQueries), 'templates must load when the picker opens, not on chat open');
    }

    /**
     * Boolean mode reads + - > < ( ) ~ * " @ as operators. An agent searching
     * "re-order" or an email address must not get an inverted match or a syntax
     * error, and a term that is nothing but operators must fall back to LIKE.
     */
    public function test_full_text_terms_strip_boolean_operators(): void
    {
        $method = new \ReflectionMethod(\App\Livewire\Chat\ConversationList::class, 'booleanModeTerm');
        $list = new \App\Livewire\Chat\ConversationList;

        $cases = [
            'invoice' => '+invoice*',
            're-order' => '+order*',               // '-' would have excluded "order";
                                                   // 're' is below the index's token size
            '@acme.com' => '+acme* +com*',         // '@' is the distance operator
            '+must -not' => '+must* +not*',
            '  spaced   out  ' => '+spaced* +out*',
            'José Ünicode' => '+José* +Ünicode*',  // accents are word characters
            '@@@' => '',                           // nothing usable -> caller uses LIKE
            '***' => '',
            'a"b(c)' => '',                        // every token too short -> LIKE
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame($expected, $method->invoke($list, $input), "term [{$input}]");
        }
    }

    /** Short terms skip the unindexed message-body scan but still match contacts. */
    public function test_short_search_terms_skip_the_message_body_scan(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $conversation = Conversation::factory()->create(['team_id' => $team->id]);
        $conversation->contact->update(['name' => 'Zoe']);
        Message::factory()->create(['conversation_id' => $conversation->id, 'content' => 'quarterly invoice attached']);

        $component = Livewire::test(\App\Livewire\Chat\ConversationList::class);

        // 2 chars: contact name still matches, message body must not be scanned.
        DB::enableQueryLog();
        $component->set('search', 'Zo')->assertSee('Zoe');
        $shortScan = array_filter(array_column(DB::getQueryLog(), 'query'), fn ($q) => str_contains($q, 'content'));
        DB::disableQueryLog();

        $this->assertSame([], array_values($shortScan), 'two-character search must not scan message bodies');

        // 3 chars: message body search is back on.
        $component->set('search', 'invoice')->assertSee('Zoe');
    }

    /**
     * Guards the regression this replaced: loadConversation() used to hydrate 50
     * Message models on mount AND on every subsequent action, duplicating the work
     * loadMessagesJson() already does.
     */
    public function test_actions_do_not_re_query_the_message_list(): void
    {
        $conversation = $this->seedConversation();
        $component = Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id]);

        DB::enableQueryLog();
        $component->call('loadConversation');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $hydrations = array_filter(
            array_column($queries, 'query'),
            fn ($q) => preg_match('/select \*.*from ["`]messages["`]/i', $q) === 1
        );

        // It may aggregate (max id) and project single columns (read receipts), but
        // it must never hydrate whole Message rows — nothing reads them.
        $this->assertSame([], array_values($hydrations), 'loadConversation must not hydrate the message list');
    }
}

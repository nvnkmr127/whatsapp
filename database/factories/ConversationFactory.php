<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition()
    {
        return [
            'team_id' => \App\Models\Team::factory(),
            // Inherit the conversation's team rather than spinning up a second
            // one: a conversation whose contact belongs to a different tenant is
            // not a state the app can produce, and building fixtures that way
            // made every tenant-scoped query in tests behave unrealistically.
            'contact_id' => fn (array $attributes) => \App\Models\Contact::factory()->create([
                'team_id' => $attributes['team_id'],
            ])->id,
            'status' => 'open',
            'last_message_at' => now(),
        ];
    }
}

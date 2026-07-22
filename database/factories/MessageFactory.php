<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition()
    {
        return [
            // When the caller supplies a conversation, take its team and contact
            // instead of inventing new ones — a message in a different tenant to
            // its own conversation is not a state the app can produce, and
            // fixtures built that way make tenant-scoped queries lie.
            'team_id' => fn (array $attributes) => isset($attributes['conversation_id'])
                ? \App\Models\Conversation::withoutGlobalScopes()->find($attributes['conversation_id'])?->team_id
                    ?? \App\Models\Team::factory()
                : \App\Models\Team::factory(),
            'contact_id' => fn (array $attributes) => isset($attributes['conversation_id'])
                ? \App\Models\Conversation::withoutGlobalScopes()->find($attributes['conversation_id'])?->contact_id
                    ?? \App\Models\Contact::factory()
                : \App\Models\Contact::factory(),
            'content' => $this->faker->sentence,
            'type' => 'text',
            'status' => 'sent',
            'direction' => 'outbound',
            'whatsapp_message_id' => 'mid.'.$this->faker->uuid,
        ];
    }
}

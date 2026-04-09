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
            'contact_id' => \App\Models\Contact::factory(),
            'status' => 'open',
            'last_message_at' => now(),
        ];
    }
}

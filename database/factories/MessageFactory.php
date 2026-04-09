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
            'team_id' => \App\Models\Team::factory(),
            'contact_id' => \App\Models\Contact::factory(),
            'content' => $this->faker->sentence,
            'type' => 'text',
            'status' => 'sent',
            'direction' => 'outbound',
            'whatsapp_message_id' => 'mid.'.$this->faker->uuid,
        ];
    }
}

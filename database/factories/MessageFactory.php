<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition()
    {
        return [
            'team_id' => 1,
            'contact_id' => Contact::factory(),
            'content' => $this->faker->sentence,
            'type' => 'text',
            'status' => 'sent',
            'direction' => 'inbound',
            'whatsapp_message_id' => 'mid.' . $this->faker->uuid,
        ];
    }
}

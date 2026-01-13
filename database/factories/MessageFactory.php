<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Message;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'contact_id' => Contact::factory(),
            'whatsapp_message_id' => 'wamid.' . $this->faker->uuid(),
            'direction' => 'inbound',
            'type' => 'text',
            'content' => $this->faker->sentence(),
            'metadata' => ['key' => 'value'],
            'status' => 'delivered',
            'sent_at' => now(),
            'delivered_at' => now(),
        ];
    }
}

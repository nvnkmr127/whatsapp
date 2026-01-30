<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message;

    public function definition()
    {
        return [
            'team_id' => 1,
            'content' => $this->faker->sentence,
            'type' => 'text',
            'status' => 'sent',
            'is_outbound' => false,
            'message_id' => 'mid.' . $this->faker->uuid,
        ];
    }
}

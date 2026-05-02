<?php

namespace Database\Factories;

use App\Models\MessageBot;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageBotFactory extends Factory
{
    protected $model = MessageBot::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->word . ' Bot',
            'reply_text' => $this->faker->paragraph,
            'trigger' => [$this->faker->word],
            'is_bot_active' => true,
        ];
    }
}

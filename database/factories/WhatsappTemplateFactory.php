<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappTemplateFactory extends Factory
{
    protected $model = WhatsappTemplate::class;

    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->unique()->word,
            'category' => 'UTILITY',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => $this->faker->sentence],
            ],
            'is_paused' => false,
        ];
    }
}

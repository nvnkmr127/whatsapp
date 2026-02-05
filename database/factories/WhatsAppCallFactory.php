<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\WhatsAppCall;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsAppCallFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WhatsAppCall::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'team_id' => Team::factory(),
            'call_id' => 'wacid.' . $this->faker->uuid,
            'direction' => 'inbound',
            'status' => 'initiated',
            'from_number' => $this->faker->phoneNumber,
            'to_number' => $this->faker->phoneNumber,
            'initiated_at' => now(),
            'metadata' => [],
        ];
    }
}

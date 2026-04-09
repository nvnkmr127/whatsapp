<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition()
    {
        return [
            'team_id' => \App\Models\Team::factory(),
            'name' => $this->faker->name,
            'phone_number' => $this->faker->phoneNumber,
            'opt_in_status' => 'opted_in',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    protected static $cache = [
        'countries' => null,
        'users'     => null,
        'statuses'  => null,
        'sources'   => null,
    ];

    protected function getRandomId(string $table): ?int
    {
        if (static::$cache[$table] === null) {
            static::$cache[$table] = DB::table($table)->pluck('id')->toArray();
        }

        return ! empty(static::$cache[$table]) ? $this->faker->randomElement(static::$cache[$table]) : null;
    }

    public function definition()
    {
        $createdAt    = now()->subDays(rand(1, 365));
        $dateAssigned = now()->subDays(rand(1, 30));

        return [
            'firstname'             => $this->faker->firstName(),
            'lastname'              => $this->faker->lastName(),
            'company'               => $this->faker->optional(0.7)->company(),
            'type'                  => $this->faker->randomElement(['lead', 'customer']),
            'description'           => $this->faker->optional(0.6)->paragraph(),
            'country_id'            => $this->getRandomId('countries'),
            'zip'                   => $this->faker->optional(0.8)->postcode(),
            'city'                  => $this->faker->optional(0.8)->city(),
            'state'                 => $this->faker->optional(0.8)->state(),
            'address'               => $this->faker->optional(0.8)->streetAddress(),
            'assigned_id'           => $this->getRandomId('users'),
            'status_id'             => $this->getRandomId('statuses'),
            'source_id'             => $this->getRandomId('sources'),
            'email'                 => $this->faker->optional(0.9)->safeEmail(),
            'website'               => $this->faker->optional(0.4)->url(),
            'phone'                 => $this->faker->e164PhoneNumber(),
            'hash'                  => Str::random(32),
            'from_form_id'          => 0,
            'addedfrom'             => $this->getRandomId('users'),
            'lastcontact'           => $this->faker->boolean(70) ? now()->subDays(rand(1, 30))->format('Y-m-d H:i:s') : null,
            'dateassigned'          => $dateAssigned->format('Y-m-d H:i:s'),
            'last_status_change'    => $this->faker->boolean(80) ? now()->subDays(rand(1, 30))->format('Y-m-d H:i:s') : null,
            'email_integration_uid' => '0',
            'default_language'      => $this->faker->optional(0.3)->languageCode(),
            'created_at'            => $createdAt->format('Y-m-d H:i:s'),
            'updated_at'            => now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
        ];
    }
}

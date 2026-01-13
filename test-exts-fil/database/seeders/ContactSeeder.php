<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactSeeder extends Seeder
{
    protected int $totalContacts = 50;

    protected int $chunkSize = 100;

    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    protected function generateContact()
    {
        $createdAt = now()->subDays(rand(1, 365));

        return [
            'firstname'          => $this->faker->firstName(),
            'lastname'           => $this->faker->lastName(),
            'company'            => $this->faker->optional(0.7)->company(),
            'type'               => $this->faker->randomElement(['lead', 'customer']),
            'description'        => $this->faker->optional(0.6)->paragraph(),
            'country_id'         => DB::table('countries')->inRandomOrder()->first()->id,
            'zip'                => $this->faker->optional(0.8)->postcode(),
            'city'               => $this->faker->optional(0.8)->city(),
            'state'              => $this->faker->optional(0.8)->state(),
            'address'            => $this->faker->optional(0.8)->streetAddress(),
            'assigned_id'        => DB::table('users')->inRandomOrder()->first()->id,
            'status_id'          => DB::table('statuses')->inRandomOrder()->first()->id,
            'source_id'          => DB::table('sources')->inRandomOrder()->first()->id,
            'email'              => $this->faker->optional(0.9)->safeEmail(),
            'website'            => $this->faker->optional(0.4)->url(),
            'phone'              => $this->faker->e164PhoneNumber(),
            'addedfrom'          => DB::table('users')->inRandomOrder()->first()->id,
            'dateassigned'       => now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
            'last_status_change' => $this->faker->boolean(80) ? now()->subDays(rand(1, 30))->format('Y-m-d H:i:s') : null,
            'default_language'   => $this->faker->optional(0.3)->languageCode(),
            'created_at'         => $createdAt->format('Y-m-d H:i:s'),
            'updated_at'         => now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
        ];
    }

    public function run()
    {
        $this->command->info('Starting Contact Seeder...');

        try {
            // Cache related IDs
            $userIds    = DB::table('users')->pluck('id')->toArray();
            $statusIds  = DB::table('statuses')->pluck('id')->toArray();
            $sourceIds  = DB::table('sources')->pluck('id')->toArray();
            $countryIds = DB::table('countries')->pluck('id')->toArray();

            if (empty($userIds) || empty($statusIds) || empty($sourceIds) || empty($countryIds)) {
                $this->command->error('Required data missing in related tables!');

                return;
            }

            $chunks = ceil($this->totalContacts / $this->chunkSize);
            $bar    = $this->command->getOutput()->createProgressBar($chunks);

            DB::beginTransaction();

            for ($i = 0; $i < $chunks; $i++) {
                $contacts = [];

                for ($j = 0; $j < $this->chunkSize; $j++) {
                    $contacts[] = $this->generateContact();
                }

                DB::table('contacts')->insert($contacts);
                $bar->advance();
            }

            DB::commit();

            $bar->finish();
            $this->command->info("\nSuccessfully created " . number_format($this->totalContacts) . ' contacts!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("\nError creating contacts: " . $e->getMessage());

            if ($e->getPrevious()) {
                $this->command->error('SQL: ' . $e->getPrevious()->getMessage());
            }
        }
    }
}

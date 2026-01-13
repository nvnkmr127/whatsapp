<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            DB::table('notifications')->insert([
                'id'              => $faker->uuid,
                'type'            => $faker->word,
                'notifiable_type' => 'App\\Models\\User', // or any other model
                'notifiable_id'   => $faker->randomNumber(),
                'data'            => $faker->text,
                'read_at'         => $faker->boolean ? now() : null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}

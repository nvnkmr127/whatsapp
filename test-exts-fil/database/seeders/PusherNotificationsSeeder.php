<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PusherNotificationsSeeder extends Seeder
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
            DB::table('pusher_notifications')->insert([
                'isread'          => $faker->randomElement([0, 1]),
                'isread_inline'   => $faker->boolean,
                'date'            => now(),
                'description'     => $faker->paragraph,
                'fromuserid'      => $faker->randomNumber(),
                'fromclientid'    => $faker->randomNumber(),
                'from_fullname'   => $faker->name,
                'touserid'        => $faker->randomNumber(),
                'fromcompany'     => $faker->randomNumber(),
                'link'            => $faker->url,
                'additional_data' => $faker->text,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}

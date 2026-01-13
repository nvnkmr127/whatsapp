<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatSeeder extends Seeder
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
            DB::table('chat')->insert([
                'name'            => $faker->word,
                'receiver_id'     => $faker->randomNumber(5),
                'last_message'    => $faker->sentence,
                'last_msg_time'   => $faker->dateTime,
                'wa_no'           => $faker->phoneNumber,
                'wa_no_id'        => $faker->randomNumber(5),
                'time_sent'       => now(),
                'type'            => $faker->word,
                'type_id'         => $faker->uuid,
                'agent'           => $faker->name,
                'is_ai_chat'      => $faker->boolean,
                'ai_message_json' => $faker->text,
                'is_bots_stoped'  => $faker->boolean,
                'bot_stoped_time' => $faker->dateTime,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}

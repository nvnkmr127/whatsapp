<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatMessagesSeeder extends Seeder
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
            DB::table('chat_messages')->insert([
                'interaction_id' => $faker->randomNumber(),
                'sender_id'      => $faker->randomNumber(),
                'url'            => $faker->url,
                'message'        => $faker->paragraph,
                'status'         => $faker->randomElement(['sent', 'delivered', 'read']),
                'time_sent'      => now(),
                'message_id'     => $faker->uuid,
                'staff_id'       => $faker->uuid,
                'type'           => $faker->word,
                'is_read'        => $faker->boolean,
                'ref_message_id' => $faker->uuid,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}

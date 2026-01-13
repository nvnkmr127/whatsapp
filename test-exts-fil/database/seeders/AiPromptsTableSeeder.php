<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiPromptsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data[] = [
                'name'       => 'Prompt ' . $i,
                'action'     => 'This is the AI action for Prompt ' . $i . '.',
                'is_public'  => rand(0, 1),
                'added_from' => rand(1, 50), // Assuming 50 users
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('ai_prompts')->insert($data);
    }
}

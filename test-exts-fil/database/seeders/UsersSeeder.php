<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (range(1, 100) as $index) {
            User::create([
                'name'       => 'User ' . $index,
                'email'      => 'user' . $index . '@example.com',
                'password'   => Hash::make('password'),
                'role_id'    => rand(1, 5), // Assuming you have roles in the `roles` table
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

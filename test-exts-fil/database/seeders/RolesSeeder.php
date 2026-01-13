<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            'Admin',
            'Manager',
            'User',
            'Support',
            'Guest',
        ];

        foreach ($roles as $role) {
            Role::create([
                'name'       => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

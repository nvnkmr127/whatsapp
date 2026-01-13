<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'New', 'color' => '#4CAF50', 'isdefault' => 1],
            ['name' => 'In Progress', 'color' => '#2196F3', 'isdefault' => 0],
            ['name' => 'Contacted', 'color' => '#FFC107', 'isdefault' => 0],
            ['name' => 'Qualified', 'color' => '#9C27B0', 'isdefault' => 0],
            ['name' => 'Closed', 'color' => '#F44336', 'isdefault' => 0],
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(
                ['name' => $status['name']],
                $status
            );
        }
    }
}

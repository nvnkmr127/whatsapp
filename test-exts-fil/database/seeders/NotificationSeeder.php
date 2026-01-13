<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        $notifications = [
            [
                'message'    => 'Caleb Flakelar commented on Admin',
                'type'       => 'comment',
                'created_at' => now(),
            ],
            [
                'message'    => 'New message received from John Doe',
                'type'       => 'message',
                'created_at' => now()->subDay(),
            ],
            [
                'message'    => 'System update completed successfully',
                'type'       => 'alert',
                'created_at' => now()->subDay(),
            ],
            [
                'message'    => 'Sarah Johnson replied to your comment',
                'type'       => 'comment',
                'created_at' => now()->subDay(),
            ],
            [
                'message'    => 'Your profile was updated successfully',
                'type'       => 'success',
                'created_at' => now()->subDay(),
            ],
        ];

        foreach ($notifications as $notification) {
            DB::table('notifications')->insert([
                'id'              => Str::uuid(),
                'type'            => $notification['type'],
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id'   => 1,
                'data'            => json_encode([
                    'message' => $notification['message'],
                    'type'    => $notification['type'],
                ]),
                'read_at'    => null,
                'created_at' => $notification['created_at'],
                'updated_at' => $notification['created_at'],
            ]);
        }
    }
}

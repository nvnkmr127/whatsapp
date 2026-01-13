<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WmActivityLogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data[] = [
                'phone_number_id'     => rand(100000000000000, 999999999999999), // Random 15-digit number
                'access_token'        => 'EAAKagdNgZBCYBOyCtC8zEMfstNsocgfxbQnrOjZBsbKbpsf148mDKLHId8oZAxCZCH' . rand(1000, 9999),
                'business_account_id' => rand(100000000000000, 999999999999999), // Random 15-digit number
                'response_code'       => rand(200, 500), // Simulating HTTP response codes
                'client_id'           => rand(1, 50), // Assuming 50 possible clients
                'response_data'       => json_encode([
                    'messaging_product' => 'whatsapp',
                    'contacts'          => [
                        [
                            'input' => '91' . rand(7000000000, 9999999999), // Random 10-digit Indian phone number
                            'wa_id' => '91' . rand(7000000000, 9999999999),
                        ],
                    ],
                    'messages' => [
                        [
                            'id' => 'wamid.HBgMOTE5OTI1MTE5Mjg0FQIAERgSMENGREJFMjNDREE4REZBNTEwAA==',
                        ],
                    ],
                ]),
                'category'        => 'Bot Flow Builder',
                'category_id'     => rand(1, 10), // Assuming 10 categories
                'rel_type'        => 'leads',
                'rel_id'          => rand(0, 10), // Example relational ID
                'category_params' => json_encode([
                    'message' => "\nHello! How can I assist you today?\nfooter",
                ]),
                'raw_data' => json_encode([
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => '91' . rand(7000000000, 9999999999),
                    'type'              => 'text',
                    'text'              => [
                        'preview_url' => true,
                        'body'        => "\nHello! How can I assist you today?\nfooter",
                    ],
                ]),
                'recorded_at' => now()->subDays(rand(0, 30)), // Random date within the last 30 days
            ];
        }

        DB::table('wm_activity_logs')->insert($data);
    }
}

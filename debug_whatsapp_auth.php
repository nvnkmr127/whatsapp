<?php

use Illuminate\Support\Facades\Http;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$teams = \App\Models\Team::all();

echo 'Found '.$teams->count()." teams.\n";

foreach ($teams as $team) {
    echo 'Team ID: '.$team->id."\n";
    echo '  Name: '.$team->name."\n";
    $token = $team->whatsapp_access_token;
    echo '  Token Length: '.strlen($token)."\n";

    if (strlen($token) > 0) {
        $appId = config('whatsapp.app_id') ?? config('services.facebook.client_id');
        $appSecret = config('whatsapp.app_secret') ?? config('services.facebook.client_secret');

        $appToken = $appId.'|'.$appSecret;

        echo "  Debugging token for Team {$team->id}...\n";

        try {
            $response = Http::get('https://graph.facebook.com/debug_token', [
                'input_token' => $token,
                'access_token' => $appToken,
            ]);

            echo '  Response Status: '.$response->status()."\n";
            $json = $response->json();

            if (isset($json['data']['app_id'])) {
                echo '  Token App ID: '.$json['data']['app_id']."\n";
                if ($json['data']['app_id'] != $appId) {
                    echo "  MISMATCH DETECTED! Config ($appId) != Token ({$json['data']['app_id']})\n";
                } else {
                    echo "  App IDs match.\n";
                }
            } else {
                echo '  Error/No Data: '.json_encode($json)."\n";
            }
        } catch (\Exception $e) {
            echo '  Exception: '.$e->getMessage()."\n";
        }
    } else {
        echo "  Skipping (No Config/Token)\n";
    }
    echo "---------------------------------------------------\n";
}

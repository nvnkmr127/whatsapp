<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use Illuminate\Database\Seeder;

class WhatsappTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templateData = [
            'name' => 'verification_code',
            'language' => 'en_US',
            'category' => 'AUTHENTICATION',
            'status' => 'APPROVED',
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => 'Your verification code is {{1}}. It expires in 5 minutes.',
                ]
            ],
            'readiness_score' => 100,
            'is_paused' => false,
        ];

        // 1. Add for System (Fallback)
        WhatsappTemplate::updateOrCreate(
            ['team_id' => null, 'name' => 'verification_code', 'language' => 'en_US'],
            $templateData
        );

        // 2. Add for all teams that have WhatsApp configured
        $teams = Team::whereNotNull('whatsapp_access_token')
            ->whereNotNull('whatsapp_phone_number_id')
            ->get();

        foreach ($teams as $team) {
            WhatsappTemplate::updateOrCreate(
                ['team_id' => $team->id, 'name' => 'verification_code', 'language' => 'en_US'],
                array_merge($templateData, ['team_id' => $team->id])
            );
        }
    }
}

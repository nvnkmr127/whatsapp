<?php

use App\Enums\IntegrationState;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\WhatsAppHealthAlert;
use App\Models\WhatsAppHealthSnapshot;
use App\Models\WhatsAppSetupAudit;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\DB;

echo "Starting WhatsApp Data Reset...\n";

try {
    DB::transaction(function () {
        // Disable foreign key checks for this session
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Clear Templates
        echo "Deleting WhatsApp Templates...\n";
        WhatsappTemplate::query()->delete();

        // 2. Clear Health Data
        echo "Deleting Health Snapshots & Alerts...\n";
        WhatsAppHealthSnapshot::query()->delete();
        WhatsAppHealthAlert::query()->delete();

        // 3. Clear Audit Logs
        echo "Deleting Setup Audits...\n";
        WhatsAppSetupAudit::query()->delete();

        // 4. Clear Messages & Conversations
        echo "Deleting all Messages and Conversations...\n";
        try {
            Message::query()->delete();
            Conversation::query()->delete();
        } catch (\Throwable $e) {
            echo 'Warning deleting messages: '.$e->getMessage()."\n";
        }

        // 5. Reset Team Configuration
        echo "Resetting Team Configurations...\n";
        Team::query()->update([
            'whatsapp_access_token' => null,
            'whatsapp_business_account_id' => null,
            'whatsapp_phone_number_id' => null,
            'whatsapp_connected' => false,
            'whatsapp_setup_state' => IntegrationState::DISCONNECTED,
            'facebook_business_id' => null,
            'whatsapp_messaging_limit' => null,
            'whatsapp_quality_rating' => null,
            'whatsapp_phone_display' => null,
            'whatsapp_verified_name' => null,
            'whatsapp_token_expires_at' => null,
            'whatsapp_token_last_validated' => null,
            'whatsapp_settings' => null,
            'outbound_webhook_url' => null,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    });
    echo "WhatsApp Data Reset Complete.\n";
} catch (\Throwable $e) {
    echo 'Error resetting data: '.$e->getMessage()."\n";
    try {
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    } catch (\Throwable $ex) {
    }
}

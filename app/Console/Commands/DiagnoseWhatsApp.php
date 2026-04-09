<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\SystemEvent;
use App\Models\Team;
use App\Models\WebhookPayload;
use Illuminate\Console\Command;

class DiagnoseWhatsApp extends Command
{
    protected $signature = 'whatsapp:diagnose {--limit=20}';

    protected $description = 'Diagnose WhatsApp delivery issues';

    public function handle()
    {
        $limit = $this->option('limit');
        $this->info('Starting WhatsApp Diagnosis...');

        // 1. Check recent messages
        $this->info("\n--- Recent Messages (Last $limit) ---");
        $messages = Message::orderBy('created_at', 'desc')->take($limit)->get();

        $stats = [
            'total' => 0,
            'sent' => 0,
            'delivered' => 0,
            'read' => 0,
            'failed' => 0,
            'queued' => 0,
        ];

        $headers = ['ID', 'To', 'Status', 'WA Message ID', 'Created At', 'Updated At'];
        $rows = [];

        foreach ($messages as $msg) {
            $stats['total']++;
            $stats[$msg->status] = ($stats[$msg->status] ?? 0) + 1;

            $rows[] = [
                $msg->id,
                $msg->to,
                $msg->status,
                substr($msg->whatsapp_message_id, 0, 15).'...',
                $msg->created_at->toDateTimeString(),
                $msg->updated_at->toDateTimeString(),
            ];
        }

        $this->table($headers, $rows);
        $this->info('Stats: '.json_encode($stats, JSON_PRETTY_PRINT));

        // 2. Check recent Webhook Payloads
        $this->info("\n--- Recent Webhook Payloads (Last $limit) ---");
        $payloads = WebhookPayload::orderBy('created_at', 'desc')->take($limit)->get();

        $headers = ['ID', 'Status', 'WABA ID', 'Error', 'Created At'];
        $rows = [];

        foreach ($payloads as $p) {
            $rows[] = [
                $p->id,
                $p->status,
                $p->waba_id,
                substr($p->error_message ?? '', 0, 30),
                $p->created_at->toDateTimeString(),
            ];
        }

        $this->table($headers, $rows);

        // 3. Check System Events for Failures
        $this->info("\n--- Recent System Errors (Last $limit) ---");
        $errors = SystemEvent::where('category', 'operational')
            ->where('event_type', 'like', 'waba.%')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        if ($errors->isEmpty()) {
            $this->info('No recent operational errors found in SystemEvent.');
        } else {
            $headers = ['Event', 'Payload Summary', 'Date'];
            $rows = [];
            foreach ($errors as $e) {
                $rows[] = [
                    $e->event_type,
                    substr(json_encode($e->payload), 0, 50),
                    $e->created_at->toDateTimeString(),
                ];
            }
            $this->table($headers, $rows);
        }

        // 4. Configuration Check
        $this->info("\n--- Configuration Check ---");
        $teams = Team::all();
        foreach ($teams as $team) {
            $this->info("Team ID: {$team->id}");
            $this->info("  Name: {$team->name}");
            $this->info('  Setup State: '.($team->whatsapp_setup_state ? (is_string($team->whatsapp_setup_state) ? $team->whatsapp_setup_state : $team->whatsapp_setup_state->name) : 'NULL'));
            $this->info('  Phone ID: '.($team->whatsapp_phone_number_id ?? 'MISSING'));
            $this->info('  WABA ID: '.($team->whatsapp_business_account_id ?? 'MISSING'));
            $this->info('  Token: '.($team->whatsapp_access_token ? 'Set (Length: '.strlen($team->whatsapp_access_token).')' : 'MISSING'));
        }

        // 5. Campaign Check
        $this->info("\n--- Recent Campaigns ---");
        $campaigns = \App\Models\Campaign::orderBy('created_at', 'desc')->take(5)->get();
        foreach ($campaigns as $c) {
            $this->info("Campaign ID: {$c->id}, Status: {$c->status}, Sent: {$c->sent_count}, Del: {$c->del_count}");
        }
    }
}

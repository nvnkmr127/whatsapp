<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class AuditWhatsappIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:whatsapp-integrity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit WhatsApp integration for integrity issues (Ghost Numbers, Orphans, Partial States)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting WhatsApp Integrity Audit...');

        // 1. Ghost Phone Numbers (Duplicates)
        $this->info('Scanning for Ghost Phone Numbers (Duplicates across teams)...');
        $duplicates = Team::select('whatsapp_phone_number_id', DB::raw('count(*) as total'))
            ->whereNotNull('whatsapp_phone_number_id')
            ->groupBy('whatsapp_phone_number_id')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->count() > 0) {
            $this->error("Found {$duplicates->count()} shared phone numbers!");
            foreach ($duplicates as $dup) {
                $teams = Team::where('whatsapp_phone_number_id', $dup->whatsapp_phone_number_id)
                    ->get(['id', 'name', 'whatsapp_setup_state']);

                $this->table(
                    ['Phone ID', 'Team ID', 'Team Name', 'State'],
                    $teams->map(fn($t) => [$dup->whatsapp_phone_number_id, $t->id, $t->name, $t->whatsapp_setup_state?->value])
                );
            }
        } else {
            $this->info('No duplicate phone numbers found. (PASS)');
        }

        // 2. Orphaned Tokens (Token exists, but WABA ID missing)
        $this->info("\nScanning for Orphaned Tokens (Token present, WABA ID missing)...");
        $orphans = Team::whereNotNull('whatsapp_access_token')
            ->whereNull('whatsapp_business_account_id')
            ->get(['id', 'name']);

        if ($orphans->count() > 0) {
            $this->warn("Found {$orphans->count()} teams with orphaned tokens.");
            $this->table(
                ['Team ID', 'Team Name'],
                $orphans->map(fn($t) => [$t->id, $t->name])
            );
        } else {
            $this->info('No orphaned tokens found. (PASS)');
        }

        // 3. Broken "Connected" State
        $this->info("\nScanning for Broken 'Connected' States...");
        $broken = Team::where('whatsapp_connected', true)
            ->where(function ($q) {
                $q->whereNull('whatsapp_access_token')
                    ->orWhereNull('whatsapp_business_account_id')
                    ->orWhereNull('whatsapp_phone_number_id');
            })
            ->get(['id', 'name', 'whatsapp_access_token', 'whatsapp_business_account_id', 'whatsapp_phone_number_id']);

        if ($broken->count() > 0) {
            $this->error("Found {$broken->count()} teams marked 'Connected' but missing data.");
            $this->table(
                ['Team ID', 'Name', 'Has Token', 'Has WABA', 'Has Phone'],
                $broken->map(fn($t) => [
                    $t->id,
                    $t->name,
                    $t->whatsapp_access_token ? 'YES' : 'NO',
                    $t->whatsapp_business_account_id ? 'YES' : 'NO',
                    $t->whatsapp_phone_number_id ? 'YES' : 'NO'
                ])
            );
        } else {
            $this->info('No broken connection states found. (PASS)');
        }

        $this->info("\nAudit Complete.");
    }
}

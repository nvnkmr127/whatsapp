<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class DisableSipCalling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:disable-sip {team_id : The team ID to disable SIP for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable SIP calling for a team\'s WhatsApp phone number to enable Graph API (WebRTC) calling';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teamId = $this->argument('team_id');

        $team = Team::find($teamId);

        if (! $team) {
            $this->error("Team with ID {$teamId} not found.");

            return 1;
        }

        if (! $team->whatsapp_phone_number_id) {
            $this->error("Team {$teamId} does not have a WhatsApp phone number configured.");

            return 1;
        }

        $this->info("Team: {$team->name}");
        $this->info("Phone Number ID: {$team->whatsapp_phone_number_id}");

        // First, check current settings
        $this->info("\nChecking current call settings...");

        try {
            $service = new WhatsAppService($team);

            $currentSettings = $service->getSystemCallSettings();

            if ($currentSettings['success'] ?? false) {
                $data = $currentSettings['data'] ?? [];
                $this->table(
                    ['Setting', 'Value'],
                    collect($data)->map(fn ($v, $k) => [$k, is_array($v) ? json_encode($v) : $v])->values()->toArray()
                );

                // Check if SIP is currently enabled
                $sipStatus = $data['sip_status'] ?? $data['sip_credentials'] ?? null;
                if ($sipStatus) {
                    $this->warn("\n⚠️  SIP is currently ENABLED. This is why Graph API calls fail with error 131055/2593026.");
                } else {
                    $this->info("\n✓ SIP does not appear to be enabled.");
                }
            } else {
                $this->warn('Could not fetch current settings: '.json_encode($currentSettings['error'] ?? 'Unknown'));
            }

            // Confirm before proceeding
            if (! $this->confirm('Do you want to disable SIP calling for this phone number?')) {
                $this->info('Operation cancelled.');

                return 0;
            }

            $this->info("\nDisabling SIP calling...");

            $response = $service->disableSipCalling();

            if ($response['success'] ?? false) {
                $this->info('✅ SIP calling disabled successfully!');
                $this->info('You can now use Graph API (WebRTC) for calls.');

                // Verify the change
                $this->info("\nVerifying new settings...");
                $newSettings = $service->getSystemCallSettings();
                if ($newSettings['success'] ?? false) {
                    $this->table(
                        ['Setting', 'Value'],
                        collect($newSettings['data'] ?? [])->map(fn ($v, $k) => [$k, is_array($v) ? json_encode($v) : $v])->values()->toArray()
                    );
                }

                return 0;
            } else {
                $this->error('❌ Failed to disable SIP calling:');
                $this->error(json_encode($response['error'] ?? 'Unknown error', JSON_PRETTY_PRINT));

                return 1;
            }

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }
}
